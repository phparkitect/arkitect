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
    public const THROWS_TYPES_ATTRIBUTE = 'docblock_throws_types';

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

        $this->resolvePropertyTypes($node);
    }

    private function resolvePropertyTypes(Node $node): void
    {
        if (!($node instanceof Stmt\Property)) {
            return;
        }

        $docblock = $this->parseDocblock($node);

        if (null === $docblock) {
            return;
        }

        $arrayItemType = $docblock->getVarTagTypes();
        $arrayItemType = array_pop($arrayItemType);

        if ($this->isTypeClass($arrayItemType)) {
            $node->type = $this->resolveName(new Name($arrayItemType), Stmt\Use_::TYPE_NORMAL);

            return;
        }

        if ($this->parseCustomAnnotations && !($node->type instanceof FullyQualified)) {
            $doctrineAnnotations = $docblock->getDoctrineLikeAnnotationTypes();
            $doctrineAnnotations = array_shift($doctrineAnnotations);

            if (null === $doctrineAnnotations) {
                return;
            }

            $node->type = $this->resolveName(new Name($doctrineAnnotations), Stmt\Use_::TYPE_NORMAL);
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

        $docblock = $this->parseDocblock($node);

        if (null === $docblock) { // no docblock, nothing to do
            return;
        }

        // extract param types from param tags
        foreach ($node->params as $param) {
            if (!$this->isTypeArray($param->type)) { // not an array, nothing to do
                continue;
            }

            if (!($param->var instanceof Expr\Variable) || !\is_string($param->var->name)) {
                continue;
            }

            $type = $docblock->getParamTagTypesByName('$'.$param->var->name);

            // we ignore any type which is not a class
            if (!$this->isTypeClass($type)) {
                continue;
            }

            $param->type = $this->resolveName(new Name($type), Stmt\Use_::TYPE_NORMAL);
        }

        $this->resolveReturnValueType($node, $docblock);

        $this->resolveThrowsValueType($node, $docblock);
    }

    /**
     * @param Stmt\ClassMethod|Stmt\Function_|Expr\Closure|Expr\ArrowFunction $node
     */
    private function resolveReturnValueType(Node $node, Docblock $docblock): void
    {
        if (null === $node->returnType) {
            return;
        }

        if (!$this->isTypeArray($node->returnType)) {
            return;
        }

        $type = $docblock->getReturnTagTypes();
        $type = array_pop($type);

        // we ignore any type which is not a class
        if (!$this->isTypeClass($type)) {
            return;
        }

        $node->returnType = $this->resolveName(new Name($type), Stmt\Use_::TYPE_NORMAL);
    }

    /**
     * @param Stmt\ClassMethod|Stmt\Function_|Expr\Closure|Expr\ArrowFunction $node
     */
    private function resolveThrowsValueType(Node $node, Docblock $docblock): void
    {
        // extract throw types from throw tag
        $throwValues = $docblock->getThrowTagsTypes();

        if (empty($throwValues)) {
            return;
        }

        $throwsTypesResolved = [];

        foreach ($throwValues as $throwValue) {
            if (str_starts_with($throwValue, '\\')) {
                $name = new FullyQualified(substr($throwValue, 1));
            } else {
                $name = $this->resolveName(new Name($throwValue), Stmt\Use_::TYPE_NORMAL);
            }

            $name->setAttribute('startLine', $node->getStartLine());

            $throwsTypesResolved[] = $name;
        }

        $node->setAttribute(self::THROWS_TYPES_ATTRIBUTE, $throwsTypesResolved);
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

    private function parseDocblock(NodeAbstract $node): ?Docblock
    {
        if (null === $node->getDocComment()) {
            return null;
        }

        /** @var Doc $docComment */
        $docComment = $node->getDocComment();

        return $this->docblockParser->parse($docComment->getText());
    }

    /**
     * @param Node\Identifier|Name|Node\ComplexType|null $type
     */
    private function isTypeArray($type): bool
    {
        return null !== $type && isset($type->name) && 'array' === $type->name;
    }

    /**
     * @psalm-assert-if-true string $fqcn
     */
    private function isTypeClass(?string $fqcn): bool
    {
        if (null === $fqcn) {
            return false;
        }

        $validFqcn = '/^[a-zA-Z0-9_\x7f-\xff\\\\]*[a-zA-Z0-9_\x7f-\xff]$/';

        return (bool) preg_match($validFqcn, $fqcn);
    }
}
