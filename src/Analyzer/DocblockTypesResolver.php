<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

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
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;

/**
 * This class is used to collect type information from dockblocks, in particular
 * - regular dockblock tags: @param, @var, @return
 * - old style annotations like @Assert\Blank
 * and assign them to the piece of code the dockblock is attached to.
 *
 * This allows to detect dependencies declared only in dockblocks
 *
 * Since the @throws tags does not have any corresponding code, we populate a custom node attribute in order to make it available
 * to subsequent visitors.
 */
class DocblockTypesResolver extends NodeVisitorAbstract
{
    protected PhpDocParser $phpDocParser;

    protected Lexer $phpDocLexer;
    private NameContext $nameContext;

    private bool $parseCustomAnnotations;

    public function __construct(bool $parseCustomAnnotations = true)
    {
        $this->nameContext = new NameContext(new ErrorHandler\Throwing());
        $this->parseCustomAnnotations = $parseCustomAnnotations;

        // this if is to allow using v 1.2 or v2
        if (class_exists(ParserConfig::class)) {
            $parserConfig = new ParserConfig([]);
            $constExprParser = new ConstExprParser($parserConfig);
            $typeParser = new TypeParser($parserConfig, $constExprParser);
            $this->phpDocParser = new PhpDocParser($parserConfig, $typeParser, $constExprParser);
            $this->phpDocLexer = new Lexer($parserConfig);
        } else {
            $typeParser = new TypeParser();
            $constExprParser = new ConstExprParser();
            $this->phpDocParser = new PhpDocParser($typeParser, $constExprParser);
            $this->phpDocLexer = new Lexer();
        }
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
            foreach ($node->uses as $use) {
                $this->addAlias($use, $node->type, null);
            }
        }

        if ($node instanceof Stmt\GroupUse) {
            foreach ($node->uses as $use) {
                $this->addAlias($use, $node->type, $node->prefix);
            }
        }

        $this->resolveFunctionSignature($node);
    }

    /**
     * Resolve name, according to name resolver options.
     *
     * @param Name              $name Function or constant name to resolve
     * @param Stmt\Use_::TYPE_* $type One of Stmt\Use_::TYPE_*
     *
     * @return Name Resolved name, or original name with attribute
     */
    protected function resolveName(Name $name, int $type): Name
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

    protected function resolveClassName(Name $name): Name
    {
        return $this->resolveName($name, Stmt\Use_::TYPE_NORMAL);
    }

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

    /**
     * @param Stmt\Function_|Stmt\ClassMethod|Expr\Closure|Expr\ArrowFunction $node
     */
    private function resolveFunctionSignature($node): void
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

    private function parseDocblock(NodeAbstract $node): ?PhpDocNode
    {
        if (null === $node->getDocComment()) {
            return null;
        }

        /** @var Doc $docComment */
        $docComment = $node->getDocComment();

        $tokens = $this->phpDocLexer->tokenize($docComment->getText());
        $tokenIterator = new TokenIterator($tokens);

        return $this->phpDocParser->parse($tokenIterator);
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

    /**
     * @psalm-suppress MissingParamType
     * @psalm-suppress PossiblyNullArgument
     * @psalm-suppress MissingReturnType
     * @psalm-suppress InvalidReturnStatement
     *
     * @template T of Node\Identifier|Name|Node\ComplexType|null
     *
     * @param T $node
     *
     * @return T
     */
    private function resolveType(?Node $node): ?Node
    {
        if ($node instanceof Name) {
            return $this->resolveClassName($node);
        }
        if ($node instanceof Node\NullableType) {
            $node->type = $this->resolveType($node->type);

            return $node;
        }
        if ($node instanceof Node\UnionType || $node instanceof Node\IntersectionType) {
            foreach ($node->types as &$type) {
                $type = $this->resolveType($type);
            }

            return $node;
        }

        return $node;
    }
}
