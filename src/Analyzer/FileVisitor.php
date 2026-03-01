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

        // handles anonymous class definition like new class() {}
        $this->handleAnonClassNode($node);

        // handles enum definition
        $this->handleEnumNode($node);

        // handles interface definition like interface MyInterface {}
        $this->handleInterfaceNode($node);

        // handles trait definition like trait MyTrait {}
        $this->handleTraitNode($node);

        // handles trait usage like use MyTrait;
        $this->handleTraitUseNode($node);

        // handles code like $constantValue = StaticClass::constant;
        $this->handleStaticClassConstantNode($node);

        // handles code like $static = StaticClass::foo();
        $this->handleStaticClassCallsNode($node);

        // handles code lik $a instanceof MyClass
        $this->handleInstanceOf($node);

        // handles code like $a = new MyClass();
        $this->handleNewExpression($node);

        // handles code like public MyClass $myClass;
        $this->handleTypedProperty($node);

        // handles docblock like /** @var MyClass $myClass */
        $this->handleDocComment($node);

        // handles code like public function myMethod(MyClass $myClass) {}
        $this->handleParamDependency($node);

        // handles code like public function myMethod(): MyClass {}
        $this->handleReturnTypeDependency($node);

        // handles attribute definition like #[MyAttribute]
        $this->handleAttributeNode($node);

        // handles property hooks like public string $name { get => ...; set { ... } }
        $this->handlePropertyHookNode($node);

        // handles throws types like @throws MyClass
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
        if (!$node instanceof Node\Stmt\Class_) {
            return;
        }

        if ($node->isAnonymous()) {
            return;
        }

        if (null !== $node->namespacedName) {
            $this->classDescriptionBuilder->setClassName($node->namespacedName->toCodeString());
        }

        foreach ($node->implements as $interface) {
            $this->classDescriptionBuilder
                ->addDependency(new ClassDependency($interface->toString(), $interface->getLine()));
        }

        if (null !== $node->extends) {
            $this->classDescriptionBuilder
                ->addDependency(new ClassDependency($node->extends->toString(), $node->getLine()));
        }

        $this->classDescriptionBuilder->setFinal($node->isFinal());

        $this->classDescriptionBuilder->setReadonly($node->isReadonly());

        $this->classDescriptionBuilder->setAbstract($node->isAbstract());
    }

    private function handleAnonClassNode(Node $node): void
    {
        if (!$node instanceof Node\Stmt\Class_) {
            return;
        }

        if (!$node->isAnonymous()) {
            return;
        }

        foreach ($node->implements as $interface) {
            $this->classDescriptionBuilder
                ->addDependency(new ClassDependency($interface->toString(), $interface->getLine()));
        }

        if (null !== $node->extends) {
            $this->classDescriptionBuilder
                ->addDependency(new ClassDependency($node->extends->toString(), $node->getLine()));
        }
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
                ->addDependency(new ClassDependency($interface->toString(), $interface->getLine()));
        }
    }

    private function handleStaticClassConstantNode(Node $node): void
    {
        if (!$node instanceof Node\Expr\ClassConstFetch) {
            return;
        }

        if (!$node->class instanceof Node\Name\FullyQualified) {
            return;
        }

        $this->classDescriptionBuilder
            ->addDependency(new ClassDependency($node->class->toString(), $node->getLine()));
    }

    private function handleStaticClassCallsNode(Node $node): void
    {
        if (!$node instanceof Node\Expr\StaticCall) {
            return;
        }

        if (!$node->class instanceof Node\Name\FullyQualified) {
            return;
        }

        $this->classDescriptionBuilder
            ->addDependency(new ClassDependency($node->class->toString(), $node->getLine()));
    }

    private function handleInstanceOf(Node $node): void
    {
        if (!$node instanceof Node\Expr\Instanceof_) {
            return;
        }

        if (!$node->class instanceof Node\Name\FullyQualified) {
            return;
        }

        $this->classDescriptionBuilder
            ->addDependency(new ClassDependency($node->class->toString(), $node->getLine()));
    }

    private function handleNewExpression(Node $node): void
    {
        if (!$node instanceof Node\Expr\New_) {
            return;
        }

        if (!$node->class instanceof Node\Name\FullyQualified) {
            return;
        }

        $this->classDescriptionBuilder
            ->addDependency(new ClassDependency($node->class->toString(), $node->getLine()));
    }

    private function handleTypedProperty(Node $node): void
    {
        if (!$node instanceof Node\Stmt\Property) {
            return;
        }

        if (null === $node->type) {
            return;
        }

        $type = $node->type instanceof NullableType ? $node->type->type : $node->type;

        if (!$type instanceof Node\Name\FullyQualified) {
            return;
        }

        $this->classDescriptionBuilder
            ->addDependency(new ClassDependency($type->toString(), $node->getLine()));
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
                ->addDependency(new ClassDependency($interface->toString(), $interface->getLine()));
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

    private function handleReturnTypeDependency(Node $node): void
    {
        if (!$node instanceof Node\Stmt\ClassMethod) {
            return;
        }

        $returnType = $node->returnType;

        if (!$returnType instanceof Node\Name\FullyQualified) {
            return;
        }

        $this->classDescriptionBuilder
            ->addDependency(new ClassDependency($returnType->toString(), $returnType->getLine()));
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

    private function addParamDependency(Node\Param $node): void
    {
        if (null === $node->type || $node->type instanceof Node\Identifier) {
            return;
        }

        $type = $node->type instanceof NullableType ? $node->type->type : $node->type;

        if (!$type instanceof Node\Name\FullyQualified) {
            return;
        }

        $this->classDescriptionBuilder
            ->addDependency(new ClassDependency($type->toString(), $node->getLine()));
    }

    private function handlePropertyHookNode(Node $node): void
    {
        if (!$node instanceof Node\PropertyHook) {
            return;
        }

        // Handle parameters in set hooks (e.g., set(MyClass $value) { ... })
        foreach ($node->params as $param) {
            $this->addParamDependency($param);
        }
    }
}
