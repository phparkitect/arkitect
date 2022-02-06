<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class FileVisitor extends NodeVisitorAbstract
{
    /** @var ClassDescriptionBuilder */
    private $classDescriptionBuilder;

    private $classDescriptions = [];

    public function enterNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\Interface_) {
            $this->classDescriptionBuilder = ClassDescriptionBuilder::create(
                $node->namespacedName->toCodeString()
            );

            foreach ($node->extends as $extend) {
                $this->classDescriptionBuilder
                    ->setExtends($extend->toString(), $node->getLine());
            }

            return;
        }

        if ($node instanceof Node\Stmt\Class_) {
            if (!$node->isAnonymous()) {
                /** @psalm-suppress UndefinedPropertyFetch */
                $this->classDescriptionBuilder = ClassDescriptionBuilder::create(
                    $node->namespacedName->toCodeString()
                );
            }

            foreach ($node->implements as $interface) {
                $this->classDescriptionBuilder
                     ->addInterface($interface->toString(), $interface->getLine());
            }

            if (null !== $node->extends) {
                $this->classDescriptionBuilder
                    ->setExtends($node->extends->toString(), $node->getLine());
            }
        }

        /**
         * adding static function classes as dependencies
         * $static = StaticClass::foo();.
         *
         * @see FileVisitorTest::test_should_returns_all_dependencies
         */
        if ($node instanceof Node\Expr\StaticCall && method_exists($node->class, 'toString')) {
            if ($this->isSelfOrStaticOrParent($node->class->toString())) {
                return;
            }

            $this->classDescriptionBuilder
                ->addDependency(new ClassDependency($node->class->toString(), $node->getLine()));
        }

        if ($node instanceof Node\Expr\Instanceof_ && method_exists($node->class, 'toString')) {
            if ($this->isSelfOrStaticOrParent($node->class->toString())) {
                return;
            }

            $this->classDescriptionBuilder
                ->addDependency(new ClassDependency($node->class->toString(), $node->getLine()));
        }

        if ($node instanceof Node\Expr\New_ && !($node->class instanceof Node\Expr\Variable)) {
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
        if ($node instanceof Node\Stmt\Class_ || $node instanceof Node\Stmt\Interface_) {
            $classDescription = $this->classDescriptionBuilder->get();

            $this->classDescriptions[$classDescription->getFQCN()] = $classDescription;
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

        if (method_exists($node->type, 'isSpecialClassName') && ($node->type->isSpecialClassName())) {
            return;
        }

        if (!method_exists($node->type, 'toString')) {
            return;
        }

        $this->classDescriptionBuilder
            ->addDependency(new ClassDependency($node->type->toString(), $node->getLine()));
    }
}
