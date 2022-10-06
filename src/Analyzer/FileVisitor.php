<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class FileVisitor extends NodeVisitorAbstract
{
    /** @var ?ClassDescriptionBuilder */
    private $classDescriptionBuilder;

    /** @var array */
    private $classDescriptions = [];

    public function enterNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\Class_) {
            if (!$node->isAnonymous() && null !== $node->namespacedName) {
                /** @psalm-suppress UndefinedPropertyFetch */
                $this->classDescriptionBuilder = ClassDescriptionBuilder::create(
                    $node->namespacedName->toCodeString()
                );
            }

            if (null === $this->classDescriptionBuilder) {
                return;
            }

            foreach ($node->implements as $interface) {
                $this->classDescriptionBuilder
                     ->addInterface($interface->toString(), $interface->getLine());
            }

            if (null !== $node->extends) {
                $this->classDescriptionBuilder
                    ->setExtends($node->extends->toString(), $node->getLine());
            }

            if ($node->isFinal()) {
                $this->classDescriptionBuilder->setFinal(true);
            }

            if ($node->isAbstract()) {
                $this->classDescriptionBuilder->setAbstract(true);
            }

            if (null !== $node->getDocComment()) {
                /** @var Doc $docComment */
                $docComment = $node->getDocComment();
                $this->classDescriptionBuilder->setDocBlock($docComment->getText());
            }

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
        if ($node instanceof Node\Expr\ClassConstFetch &&
            method_exists($node->class, 'toString') &&
            null !== $this->classDescriptionBuilder
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
        if ($node instanceof Node\Expr\StaticCall &&
            method_exists($node->class, 'toString') &&
            null !== $this->classDescriptionBuilder
        ) {
            if ($this->isSelfOrStaticOrParent($node->class->toString())) {
                return;
            }

            $this->classDescriptionBuilder
                ->addDependency(new ClassDependency($node->class->toString(), $node->getLine()));
        }

        if ($node instanceof Node\Expr\Instanceof_ &&
            method_exists($node->class, 'toString') &&
            null !== $this->classDescriptionBuilder
        ) {
            if ($this->isSelfOrStaticOrParent($node->class->toString())) {
                return;
            }

            $this->classDescriptionBuilder
                ->addDependency(new ClassDependency($node->class->toString(), $node->getLine()));
        }

        if ($node instanceof Node\Expr\New_ &&
            !($node->class instanceof Node\Expr\Variable) &&
             null !== $this->classDescriptionBuilder
        ) {
            if ((method_exists($node->class, 'isAnonymous') && $node->class->isAnonymous()) ||
                !method_exists($node->class, 'toString')) {
                return;
            }

            if ($this->isSelfOrStaticOrParent($node->class->toString())) {
                return;
            }

            $this->classDescriptionBuilder
                ->addDependency(new ClassDependency($node->class->toString(), $node->getLine()));
        }

        if ($node instanceof Node\Stmt\Enum_ && null !== $node->namespacedName) {
            /** @psalm-suppress UndefinedPropertyFetch */
            $this->classDescriptionBuilder = ClassDescriptionBuilder::create(
                $node->namespacedName->toCodeString()
            );
        }

        /**
         * matches parameters dependency in functions and method definitions like
         * public function __construct(Symfony\Component\HttpFoundation\Request $request).
         *
         * @see FileVisitorTest::test_should_returns_all_dependencies
         */
        if ($node instanceof Node\Param && null !== $this->classDescriptionBuilder) {
            $this->addParamDependency($node);
        }
    }

    public function getClassDescriptions(): array
    {
        return $this->classDescriptions;
    }

    public function clearParsedClassDescriptions(): void
    {
        $this->classDescriptions = [];
    }

    public function leaveNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\Class_ && null !== $this->classDescriptionBuilder) {
            $classDescription = $this->classDescriptionBuilder->get();

            $this->classDescriptions[] = $classDescription;
        }

        if ($node instanceof Node\Stmt\Enum_ && null !== $this->classDescriptionBuilder) {
            $classDescription = $this->classDescriptionBuilder->get();

            $this->classDescriptions[] = $classDescription;
        }
    }

    private function isSelfOrStaticOrParent(string $dependencyClass): bool
    {
        return 'self' === $dependencyClass || 'static' === $dependencyClass || 'parent' === $dependencyClass;
    }

    private function addParamDependency(Node\Param $node): void
    {
        if (null === $node->type || $node->type instanceof Node\NullableType || $node->type instanceof Node\Identifier) {
            return;
        }

        if (method_exists($node->type, 'isSpecialClassName') && $node->type->isSpecialClassName()) {
            return;
        }

        if (!method_exists($node->type, 'toString')) {
            return;
        }

        if (null === $this->classDescriptionBuilder) {
            return;
        }

        $this->classDescriptionBuilder
            ->addDependency(new ClassDependency($node->type->toString(), $node->getLine()));
    }
}
