<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

use PhpParser\Node;
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
        // class-like declarations: class, anonymous class, enum, interface, trait, use MyTrait;
        $this->handleClassNode($node);
        $this->handleEnumNode($node);
        $this->handleInterfaceNode($node);
        $this->handleTraitNode($node);
        $this->handleTraitUseNode($node);

        // dependencies from type declarations: public MyClass $a;, myMethod(MyClass $a): MyClass,
        // const MyClass FOO = null;, catch (MyException $e)
        $this->handleTypedProperty($node);
        $this->handleParamDependency($node);
        $this->handleReturnTypeDependency($node);
        $this->handleClassConstDependency($node);
        $this->handleCatchDependency($node);

        // dependencies from expressions: new MyClass(), MyClass::foo(), MyClass::CONST, $a instanceof MyClass
        $this->handleClassReferenceExpression($node);

        // attributes like #[MyAttribute], docblocks like /** @var MyClass $a */ and @throws MyClass
        $this->handleAttributeNode($node);
        $this->handleDocComment($node);
        $this->handleThrowsTags($node);
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
        if (!$node instanceof Node\Stmt\ClassLike) {
            return;
        }

        if ($node instanceof Node\Stmt\Class_ && $node->isAnonymous()) {
            return;
        }

        $this->classDescriptions[] = $this->classDescriptionBuilder->build();
        $this->classDescriptionBuilder->clear();
    }

    private function handleClassNode(Node $node): void
    {
        if (!$node instanceof Node\Stmt\Class_) {
            return;
        }

        if ($node->isAnonymous()) {
            // an anonymous class is not a class description of its own:
            // what it extends and implements are dependencies of the class defining it
            foreach ($node->implements as $interface) {
                $this->classDescriptionBuilder
                    ->addDependency(new ClassDependency($interface->toString(), $interface->getLine()));
            }

            if (null !== $node->extends) {
                $this->classDescriptionBuilder
                    ->addDependency(new ClassDependency($node->extends->toString(), $node->getLine()));
            }

            return;
        }

        if (null !== $node->namespacedName) {
            $this->classDescriptionBuilder->setClassName($node->namespacedName->toCodeString());
        }

        foreach ($node->implements as $interface) {
            $this->classDescriptionBuilder
                ->addInterface($interface->toString(), $interface->getLine());
        }

        if (null !== $node->extends) {
            $this->classDescriptionBuilder
                ->addExtends($node->extends->toString(), $node->getLine());
        }

        $this->classDescriptionBuilder->setFinal($node->isFinal());

        $this->classDescriptionBuilder->setReadonly($node->isReadonly());

        $this->classDescriptionBuilder->setAbstract($node->isAbstract());
    }

    private function handleEnumNode(Node $node): void
    {
        if (!$node instanceof Node\Stmt\Enum_) {
            return;
        }

        if (null == $node->namespacedName) {
            return;
        }

        $this->classDescriptionBuilder->setClassName($node->namespacedName->toCodeString());
        $this->classDescriptionBuilder->setEnum(true);

        foreach ($node->implements as $interface) {
            $this->classDescriptionBuilder
                ->addInterface($interface->toString(), $interface->getLine());
        }
    }

    private function handleInterfaceNode(Node $node): void
    {
        if (!$node instanceof Node\Stmt\Interface_) {
            return;
        }

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

    private function handleTraitNode(Node $node): void
    {
        if (!$node instanceof Node\Stmt\Trait_) {
            return;
        }

        if (null === $node->namespacedName) {
            return;
        }

        $this->classDescriptionBuilder->setClassName($node->namespacedName->toCodeString());
        $this->classDescriptionBuilder->setTrait(true);
    }

    private function handleTraitUseNode(Node $node): void
    {
        if (!$node instanceof Node\Stmt\TraitUse) {
            return;
        }

        foreach ($node->traits as $trait) {
            $this->classDescriptionBuilder
                ->addTrait($trait->toString(), $trait->getLine());
        }
    }

    private function handleTypedProperty(Node $node): void
    {
        if (!$node instanceof Node\Stmt\Property) {
            return;
        }

        foreach ($this->extractFullyQualifiedTypes($node->type) as $type) {
            $this->classDescriptionBuilder
                ->addDependency(new ClassDependency($type->toString(), $node->getLine()));
        }
    }

    private function handleParamDependency(Node $node): void
    {
        if (!$node instanceof Node\Param) {
            return;
        }

        foreach ($this->extractFullyQualifiedTypes($node->type) as $type) {
            $this->classDescriptionBuilder
                ->addDependency(new ClassDependency($type->toString(), $node->getLine()));
        }
    }

    private function handleReturnTypeDependency(Node $node): void
    {
        if (!$node instanceof Node\FunctionLike) {
            return;
        }

        foreach ($this->extractFullyQualifiedTypes($node->getReturnType()) as $returnType) {
            $this->classDescriptionBuilder
                ->addDependency(new ClassDependency($returnType->toString(), $returnType->getLine()));
        }
    }

    private function handleClassConstDependency(Node $node): void
    {
        if (!$node instanceof Node\Stmt\ClassConst) {
            return;
        }

        foreach ($this->extractFullyQualifiedTypes($node->type) as $type) {
            $this->classDescriptionBuilder
                ->addDependency(new ClassDependency($type->toString(), $node->getLine()));
        }
    }

    private function handleCatchDependency(Node $node): void
    {
        if (!$node instanceof Node\Stmt\Catch_) {
            return;
        }

        foreach ($node->types as $type) {
            $this->classDescriptionBuilder
                ->addDependency(new ClassDependency($type->toString(), $node->getLine()));
        }
    }

    private function handleClassReferenceExpression(Node $node): void
    {
        if (!$node instanceof Node\Expr\New_
            && !$node instanceof Node\Expr\StaticCall
            && !$node instanceof Node\Expr\ClassConstFetch
            && !$node instanceof Node\Expr\Instanceof_) {
            return;
        }

        if (!$node->class instanceof Node\Name\FullyQualified) {
            return;
        }

        $this->classDescriptionBuilder
            ->addDependency(new ClassDependency($node->class->toString(), $node->getLine()));
    }

    private function handleAttributeNode(Node $node): void
    {
        if (!$node instanceof Node\Attribute) {
            return;
        }

        $nodeName = $node->name;

        if (!$nodeName instanceof Node\Name\FullyQualified) {
            return;
        }

        $this->classDescriptionBuilder
            ->addAttribute($node->name->toString(), $node->getLine());
    }

    private function handleDocComment(Node $node): void
    {
        $docComment = $node->getDocComment();

        if (null === $docComment) {
            return;
        }

        $this->classDescriptionBuilder->addDocBlock($docComment->getText());
    }

    private function handleThrowsTags(Node $node): void
    {
        if (!$node->hasAttribute(DocblockTypesResolver::THROWS_TYPES_ATTRIBUTE)) {
            return;
        }

        /** @var Node\Name\FullyQualified $throw */
        foreach ($node->getAttribute(DocblockTypesResolver::THROWS_TYPES_ATTRIBUTE) as $throw) {
            $this->classDescriptionBuilder
                ->addDependency(new ClassDependency($throw->toString(), $throw->getLine()));
        }
    }

    /**
     * Flattens a type node into the class names it references, unwrapping
     * nullable, union, intersection and DNF types.
     *
     * @return list<Node\Name\FullyQualified>
     */
    private function extractFullyQualifiedTypes(?Node $type): array
    {
        if ($type instanceof Node\NullableType) {
            return $this->extractFullyQualifiedTypes($type->type);
        }

        if ($type instanceof Node\UnionType || $type instanceof Node\IntersectionType) {
            $types = [];
            foreach ($type->types as $innerType) {
                $types = array_merge($types, $this->extractFullyQualifiedTypes($innerType));
            }

            return $types;
        }

        if ($type instanceof Node\Name\FullyQualified) {
            return [$type];
        }

        return [];
    }
}
