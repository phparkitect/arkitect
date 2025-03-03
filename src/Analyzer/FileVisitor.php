<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\NullableType;
use PhpParser\NodeVisitorAbstract;

class FileVisitor extends NodeVisitorAbstract
{
    /** @var ClassDescriptionBuilder */
    private $classDescriptionBuilder;

    /** @var array */
    private $classDescriptions = [];

    public function __construct(ClassDescriptionBuilder $classDescriptionBuilder)
    {
        $this->classDescriptionBuilder = $classDescriptionBuilder;
    }

    public function enterNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\Class_) {
            if (!$node->isAnonymous() && null !== $node->namespacedName) {
                $this->classDescriptionBuilder->setClassName($node->namespacedName->toCodeString());
            }

            foreach ($node->implements as $interface) {
                $this->classDescriptionBuilder
                    ->addInterface($interface->toString(), $interface->getLine());
            }

            if (!$node->isAnonymous() && null !== $node->extends) {
                $this->classDescriptionBuilder
                    ->setExtends($node->extends->toString(), $node->getLine());
            }

            if ($node->isFinal()) {
                $this->classDescriptionBuilder->setFinal(true);
            }

            if ($node->isReadonly()) {
                $this->classDescriptionBuilder->setReadonly(true);
            }

            if ($node->isAbstract()) {
                $this->classDescriptionBuilder->setAbstract(true);
            }

            foreach ($node->attrGroups as $attributeGroup) {
                foreach ($attributeGroup->attrs as $attribute) {
                    $this->classDescriptionBuilder
                        ->addAttribute($attribute->name->toString(), $attribute->getLine());
                }
            }
        }

        if ($node instanceof Node\Stmt\Enum_ && null !== $node->namespacedName) {
            $this->classDescriptionBuilder->setClassName($node->namespacedName->toCodeString());
            $this->classDescriptionBuilder->setEnum(true);

            foreach ($node->attrGroups as $attributeGroup) {
                foreach ($attributeGroup->attrs as $attribute) {
                    $this->classDescriptionBuilder
                        ->addAttribute($attribute->name->toString(), $attribute->getLine());
                }
            }
        }

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

        /**
         * matches parameters dependency in property definitions like
         * public ?NotBlank $foo;.
         *
         * @see FileVisitorTest::test_it_parse_typed_property
         */
        if ($node instanceof Node\Stmt\Property) {
            if (null === $node->type) {
                return;
            }

            $type = $node->type;
            if ($type instanceof NullableType) {
                /** @var NullableType * */
                $nullableType = $type;
                $type = $nullableType->type;
            }

            if (!method_exists($type, 'toString')) {
                return;
            }

            if ($this->isBuiltInType($type->toString())) {
                return;
            }

            try {
                $this->classDescriptionBuilder->addDependency(new ClassDependency($type->toString(), $node->getLine()));
            } catch (\Exception $e) {
            }
        }

        if (null !== $node->getDocComment()) {
            /** @var Doc $docComment */
            $docComment = $node->getDocComment();

            $this->classDescriptionBuilder->addDocBlock($docComment->getText());
        }

        /**
         * matches parameters dependency in functions and method definitions like
         * public function __construct(Symfony\Component\HttpFoundation\Request $request).
         *
         * @see FileVisitorTest::test_should_returns_all_dependencies
         */
        if ($node instanceof Node\Param) {
            $this->addParamDependency($node);
        }

        if ($node instanceof Node\Stmt\Interface_) {
            if (null === $node->namespacedName) {
                return;
            }

            $this->classDescriptionBuilder->setClassName($node->namespacedName->toCodeString());
            $this->classDescriptionBuilder->setInterface(true);

            foreach ($node->attrGroups as $attributeGroup) {
                foreach ($attributeGroup->attrs as $attribute) {
                    $this->classDescriptionBuilder
                        ->addAttribute($attribute->name->toString(), $attribute->getLine());
                }
            }
        }

        if ($node instanceof Node\Stmt\Trait_) {
            if (null === $node->namespacedName) {
                return;
            }

            $this->classDescriptionBuilder->setClassName($node->namespacedName->toCodeString());
            $this->classDescriptionBuilder->setTrait(true);

            foreach ($node->attrGroups as $attributeGroup) {
                foreach ($attributeGroup->attrs as $attribute) {
                    $this->classDescriptionBuilder
                        ->addAttribute($attribute->name->toString(), $attribute->getLine());
                }
            }
        }

        if ($node instanceof Node\Stmt\ClassMethod) {
            $returnType = $node->returnType;
            if ($returnType instanceof Node\Name\FullyQualified) {
                $this->classDescriptionBuilder
                    ->addDependency(new ClassDependency($returnType->toString(), $returnType->getLine()));
            }
        }

        /* if ($node instanceof Node\Attribute) {
            $nodeName = $node->name;

            dump(sprintf("%s %s", $node->name, __LINE__));


            if ($nodeName instanceof Node\Name\FullyQualified) {
                $this->classDescriptionBuilder
                    ->addDependency(new ClassDependency(implode('\\', $nodeName->getParts()), $node->getLine()));
            }
        }*/
    }

    public function getClassDescriptions(): array
    {
        return $this->classDescriptions;
    }

    public function clearParsedClassDescriptions(): void
    {
        $this->classDescriptions = [];
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
