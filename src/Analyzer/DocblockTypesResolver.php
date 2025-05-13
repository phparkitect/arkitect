<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

use PhpParser\Comment\Doc;
use PhpParser\ErrorHandler;
use PhpParser\NameContext;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt;
use PhpParser\NodeAbstract;
use PhpParser\NodeVisitorAbstract;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * This class is used to collect type information from dockblocks, in particular
 * - regular dockblock tags: @param, @var, @return
 * - old style annotations like @Assert\Blank
 * and assign them to the piece of code the docblock is attached to.
 *
 * This allows to detect dependencies declared only in dockblocks
 */
class DocblockTypesResolver extends NodeVisitorAbstract
{
    private NameContext $nameContext;

    private bool $parseCustomAnnotations;

    private DocblockParser $docblockParser;

    public function __construct(bool $parseCustomAnnotations = true)
    {
        $this->nameContext = new NameContext(new ErrorHandler\Throwing());

        $this->parseCustomAnnotations = $parseCustomAnnotations;

        $this->docblockParser = DocblockParserFactory::create();
    }

    public function beforeTraverse(array $nodes): ?array
    {
        // this also clears the name context so there is not need to reinstantiate it
        $this->nameContext->startNamespace();

        return null;
    }

    public function enterNode(Node $node): void
    {
        if ($node instanceof Stmt\Namespace_) {
            $this->nameContext->startNamespace($node->name);
        }

        if ($node instanceof Stmt\Use_) {
            $this->addAliases($node->uses, $node->type, null);
        }

        if ($node instanceof Stmt\GroupUse) {
            $this->addAliases($node->uses, $node->type, $node->prefix);
        }

        $this->resolveFunctionTypes($node);

        $this->resolveParamTypes($node);
    }

    private function resolveParamTypes(Node $node): void
    {
        if (!($node instanceof Stmt\Property)) {
            return;
        }

        $phpDocNode = $this->parseDocblock($node);

        if (null === $phpDocNode) {
            return;
        }

        if ($this->isNodeOfTypeArray($node)) {
            $arrayItemType = null;

            foreach ($phpDocNode->getVarTagValues() as $tagValue) {
                $arrayItemType = $this->getArrayItemType($tagValue->type);
            }

            if (null !== $arrayItemType) {
                $node->type = $this->resolveName(new Name($arrayItemType), Stmt\Use_::TYPE_NORMAL);

                return;
            }
        }

        foreach ($phpDocNode->getVarTagValues() as $tagValue) {
            $type = $this->resolveName(new Name((string) $tagValue->type), Stmt\Use_::TYPE_NORMAL);
            $node->type = $type;
            break;
        }

        if ($this->parseCustomAnnotations && !($node->type instanceof FullyQualified)) {
            foreach ($phpDocNode->getTags() as $tagValue) {
                if ('@' === $tagValue->name[0] && !str_contains($tagValue->name, '@var')) {
                    $customTag = str_replace('@', '', $tagValue->name);
                    $type = $this->resolveName(new Name($customTag), Stmt\Use_::TYPE_NORMAL);
                    $node->type = $type;

                    break;
                }
            }
        }
    }

    private function resolveFunctionTypes(Node $node): void
    {
        if (
            !($node instanceof Stmt\ClassMethod
            || $node instanceof Stmt\Function_
            || $node instanceof Expr\Closure
            || $node instanceof Expr\ArrowFunction)
        ) {
            return;
        }

        $phpDocNode = $this->parseDocblock($node);

        if (null === $phpDocNode) { // no docblock, nothing to do
            return;
        }

        foreach ($node->params as $param) {
            if (!$this->isNodeOfTypeArray($param)) { // not an array, nothing to do
                continue;
            }

            foreach ($phpDocNode->getParamTagValues() as $phpDocParam) {
                if ($param->var instanceof Expr\Variable && \is_string($param->var->name) && $phpDocParam->parameterName === ('$'.$param->var->name)) {
                    $arrayItemType = $this->getArrayItemType($phpDocParam->type);

                    if (null !== $arrayItemType) {
                        $param->type = $this->resolveName(new Name($arrayItemType), Stmt\Use_::TYPE_NORMAL);
                    }
                }
            }
        }

        if ($node->returnType instanceof Node\Identifier && 'array' === $node->returnType->name) {
            $arrayItemType = null;

            foreach ($phpDocNode->getReturnTagValues() as $tagValue) {
                $arrayItemType = $this->getArrayItemType($tagValue->type);
            }

            if (null !== $arrayItemType) {
                $node->returnType = $this->resolveName(new Name($arrayItemType), Stmt\Use_::TYPE_NORMAL);
            }
        }
    }

    /**
     * Resolve name, according to name resolver options.
     *
     * @param Name              $name Function or constant name to resolve
     * @param Stmt\Use_::TYPE_* $type One of Stmt\Use_::TYPE_*
     *
     * @return Name Resolved name, or original name with attribute
     */
    private function resolveName(Name $name, int $type): Name
    {
        $resolvedName = $this->nameContext->getResolvedName($name, $type);

        if (null !== $resolvedName) {
            return $resolvedName;
        }

        // unqualified names inside a namespace cannot be resolved at compile-time
        // add the namespaced version of the name as an attribute
        $name->setAttribute('namespacedName', FullyQualified::concat(
            $this->nameContext->getNamespace(),
            $name,
            $name->getAttributes()
        ));

        return $name;
    }

    /**
     * @param array<Node\UseItem> $uses
     */
    private function addAliases(array $uses, int $type, ?Name $prefix = null): void
    {
        foreach ($uses as $useItem) {
            $this->addAlias($useItem, $type, $prefix);
        }
    }

    /**
     * @psalm-suppress PossiblyNullArgument
     * @psalm-suppress ArgumentTypeCoercion
     */
    private function addAlias(Node\UseItem $use, int $type, ?Name $prefix = null): void
    {
        // Add prefix for group uses
        $name = $prefix ? Name::concat($prefix, $use->name) : $use->name;
        // Type is determined either by individual element or whole use declaration
        $type |= $use->type;

        $this->nameContext->addAlias(
            $name,
            (string) $use->getAlias(),
            $type,
            $use->getAttributes()
        );
    }

    private function parseDocblock(NodeAbstract $node): ?PhpDocNode
    {
        if (null === $node->getDocComment()) {
            return null;
        }

        /** @var Doc $docComment */
        $docComment = $node->getDocComment();

        return $this->docblockParser->parse($docComment->getText());
    }

    /**
     * @param Node\Param|Stmt\Property $node
     */
    private function isNodeOfTypeArray($node): bool
    {
        return null !== $node->type && isset($node->type->name) && 'array' === $node->type->name;
    }

    private function getArrayItemType(TypeNode $typeNode): ?string
    {
        $arrayItemType = null;

        if ($typeNode instanceof GenericTypeNode) {
            if (1 === \count($typeNode->genericTypes)) {
                // this handles list<ClassName>
                $arrayItemType = (string) $typeNode->genericTypes[0];
            } elseif (2 === \count($typeNode->genericTypes)) {
                // this handles array<int, ClassName>
                $arrayItemType = (string) $typeNode->genericTypes[1];
            }
        }

        if ($typeNode instanceof ArrayTypeNode) {
            // this handles ClassName[]
            $arrayItemType = (string) $typeNode->type;
        }

        $validFqcn = '/^[a-zA-Z_\x7f-\xff\\\\][a-zA-Z0-9_\x7f-\xff\\\\]*[a-zA-Z0-9_\x7f-\xff]$/';

        if (null !== $arrayItemType && !(bool) preg_match($validFqcn, $arrayItemType)) {
            return null;
        }

        return $arrayItemType;
    }
}
