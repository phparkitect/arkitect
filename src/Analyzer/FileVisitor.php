<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

use PhpParser\Node;
use PhpParser\Node\NullableType;
use PhpParser\NodeVisitorAbstract;

class FileVisitor extends NodeVisitorAbstract
{
    private ClassDescriptionBuilder $classDescriptionBuilder;

    /** @var array<ClassDescription> */
    private array $classDescriptions = [];

    public function __construct(ClassDescriptionBuilder $classDescriptionBuilder)
    {
        $this->classDescriptionBuilder = $classDescriptionBuilder;
    }

    public function setFilePath(?string $filePath): void
    {
        $this->classDescriptionBuilder->setFilePath($filePath);
    }

    public function enterNode(Node $node): void
    {
        $this->handleClassNode($node);

        $this->handleEnumNode($node);

        $this->handleStaticClassConstantNode($node);

        $this->handleStaticClassCallsNode($node);

        $this->handleInstanceOf($node);

        $this->handleNewExpression($node);

        $this->handleTypedProperty($node);

        $this->handleDocComment($node);

        $this->handleParamDependency($node);

        $this->handleInterfaceNode($node);

        $this->handleTraitNode($node);

        $this->handleReturnTypeDependency($node);

        $this->handleAttributeNode($node);
    }

    public function getClassDescriptions(): array
    {
        return $this->classDescriptions;
    }

    public function clearParsedClassDescriptions(): void
    {
        $this->classDescriptions = [];
        $this->classDescriptionBuilder->setFilePath(null);
        $this->classDescriptionBuilder->clear();
    }

    public function leaveNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\Class_ && !$node->isAnonymous()) {
            $this->classDescriptions[] = $this->classDescriptionBuilder->build();
            $this->classDescriptionBuilder->clear();
        }

        if ($node instanceof Node\Stmt\Enum_) {
            $this->classDescriptions[] = $this->classDescriptionBuilder->build();
            $this->classDescriptionBuilder->clear();
        }

        if ($node instanceof Node\Stmt\Interface_) {
            $this->classDescriptions[] = $this->classDescriptionBuilder->build();
            $this->classDescriptionBuilder->clear();
        }

        if ($node instanceof Node\Stmt\Trait_) {
            $this->classDescriptions[] = $this->classDescriptionBuilder->build();
            $this->classDescriptionBuilder->clear();
        }
    }

    private function handleClassNode(Node $node): void
    {
        if (!($node instanceof Node\Stmt\Class_)) {
            return;
        }

        if (!$node->isAnonymous() && null !== $node->namespacedName) {
            $this->classDescriptionBuilder->setClassName($node->namespacedName->toCodeString());
        }

        foreach ($node->implements as $interface) {
            $this->classDescriptionBuilder
                ->addInterface($interface->toString(), $interface->getLine());
        }

        if (!$node->isAnonymous() && null !== $node->extends) {
            $this->classDescriptionBuilder
                ->addExtends($node->extends->toString(), $node->getLine());
        }

        if (!$node->isAnonymous()) {
            $this->classDescriptionBuilder->setFinal($node->isFinal());
        }

        if (!$node->isAnonymous()) {
            $this->classDescriptionBuilder->setReadonly($node->isReadonly());
        }

        if (!$node->isAnonymous()) {
            $this->classDescriptionBuilder->setAbstract($node->isAbstract());
        }
    }

    private function handleEnumNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\Enum_ && null !== $node->namespacedName) {
            $this->classDescriptionBuilder->setClassName($node->namespacedName->toCodeString());
            $this->classDescriptionBuilder->setEnum(true);

            foreach ($node->implements as $interface) {
                $this->classDescriptionBuilder
                    ->addInterface($interface->toString(), $interface->getLine());
            }
        }
    }

    private function handleStaticClassConstantNode(Node $node): void
    {
        /**
         * adding static classes as dependencies
         * $constantValue = StaticClass::constant;.
         *
         * @see FileVisitorTest::test_it_should_return_errors_for_const_outside_namespace
         */
        if (
            $node instanceof Node\Expr\ClassConstFetch
            && method_exists($node->class, 'toString')
        ) {
            if ($this->isSelfOrStaticOrParent($node->class->toString())) {
                return;
            }

            $this->classDescriptionBuilder
                ->addDependency(new ClassDependency($node->class->toString(), $node->getLine()));
        }
    }

    private function handleStaticClassCallsNode(Node $node): void
    {
        /**
         * adding static function classes as dependencies
         * $static = StaticClass::foo();.
         *
         * @see FileVisitorTest::test_should_returns_all_dependencies
         */
        if (
            $node instanceof Node\Expr\StaticCall
            && method_exists($node->class, 'toString')
        ) {
            if ($this->isSelfOrStaticOrParent($node->class->toString())) {
                return;
            }

            $this->classDescriptionBuilder
                ->addDependency(new ClassDependency($node->class->toString(), $node->getLine()));
        }
    }

    private function handleInstanceOf(Node $node): void
    {
        if (
            $node instanceof Node\Expr\Instanceof_
            && method_exists($node->class, 'toString')
        ) {
            if ($this->isSelfOrStaticOrParent($node->class->toString())) {
                return;
            }
            $this->classDescriptionBuilder
                ->addDependency(new ClassDependency($node->class->toString(), $node->getLine()));
        }
    }

    private function handleNewExpression(Node $node): void
    {
        if (
            $node instanceof Node\Expr\New_
            && !($node->class instanceof Node\Expr\Variable)
        ) {
            if ((method_exists($node->class, 'isAnonymous') && true === $node->class->isAnonymous())
                || !method_exists($node->class, 'toString')
            ) {
                return;
            }

            if ($this->isSelfOrStaticOrParent($node->class->toString())) {
                return;
            }

            $this->classDescriptionBuilder
                ->addDependency(new ClassDependency($node->class->toString(), $node->getLine()));
        }
    }

    private function handleTypedProperty(Node $node): void
    {
        if ($node instanceof Node\Stmt\Property) {
            if (null === $node->type) {
                return;
            }

            $type = $node->type;
            if ($type instanceof NullableType) {
                $type = $type->type;
            }

            if (!method_exists($type, 'toString') || $this->isBuiltInType($type->toString())) {
                return;
            }

            try {
                $this->classDescriptionBuilder
                    ->addDependency(new ClassDependency($type->toString(), $node->getLine()));
            } catch (\Exception $e) {
                // Silently ignore
            }
        }
    }

    private function handleDocComment(Node $node): void
    {
        $docComment = $node->getDocComment();

        if (null === $docComment) {
            return;
        }

        $this->classDescriptionBuilder->addDocBlock($docComment->getText());
    }

    private function handleParamDependency(Node $node): void
    {
        if ($node instanceof Node\Param) {
            $this->addParamDependency($node);
        }
    }

    private function handleInterfaceNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\Interface_) {
            if (null === $node->namespacedName) {
                return;
            }

            $this->classDescriptionBuilder->setClassName($node->namespacedName->toCodeString());
            $this->classDescriptionBuilder->setInterface(true);

            foreach ($node->extends as $interface) {
                $this->classDescriptionBuilder
                    ->addExtends($interface->toString(), $interface->getLine());
            }
        }
    }

    private function handleTraitNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\Trait_) {
            if (null === $node->namespacedName) {
                return;
            }

            $this->classDescriptionBuilder->setClassName($node->namespacedName->toCodeString());
            $this->classDescriptionBuilder->setTrait(true);
        }
    }

    private function handleReturnTypeDependency(Node $node): void
    {
        if ($node instanceof Node\Stmt\ClassMethod) {
            $returnType = $node->returnType;
            if ($returnType instanceof Node\Name\FullyQualified) {
                $this->classDescriptionBuilder
                    ->addDependency(new ClassDependency($returnType->toString(), $returnType->getLine()));
            }
        }
    }

    private function handleAttributeNode(Node $node): void
    {
        if ($node instanceof Node\Attribute) {
            $nodeName = $node->name;

            if ($nodeName instanceof Node\Name\FullyQualified) {
                $this->classDescriptionBuilder
                    ->addAttribute($node->name->toString(), $node->getLine());
            }
        }
    }

    private function isSelfOrStaticOrParent(string $dependencyClass): bool
    {
        return 'self' === $dependencyClass || 'static' === $dependencyClass || 'parent' === $dependencyClass;
    }

    private function addParamDependency(Node\Param $node): void
    {
        if (null === $node->type || $node->type instanceof Node\Identifier) {
            return;
        }

        $type = $node->type;
        if ($type instanceof NullableType) {
            /** @var NullableType * */
            $nullableType = $type;
            $type = $nullableType->type;
        }

        if (method_exists($type, 'isSpecialClassName') && true === $type->isSpecialClassName()) {
            return;
        }

        if (!method_exists($type, 'toString')) {
            return;
        }

        if ($this->isBuiltInType($type->toString())) {
            return;
        }

        $this->classDescriptionBuilder
            ->addDependency(new ClassDependency($type->toString(), $node->getLine()));
    }

    private function isBuiltInType(string $typeName): bool
    {
        return \in_array($typeName, ['bool', 'int', 'float', 'string', 'array', 'resource', 'object', 'null']);
    }
}
