<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class FileVisitor extends NodeVisitorAbstract
{
    /** @var ClassDescriptionBuilder|null */
    private $classDescriptionBuilder;

    private $classDescriptions = [];

    public function enterNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\Class_) {
            /** @psalm-suppress UndefinedPropertyFetch */
            $this->classDescriptionBuilder = ClassDescriptionBuilder::create(
                $node->namespacedName->toCodeString()
            );

            foreach ($node->implements as $interface) {
                $this->classDescriptionBuilder
                     ->addInterface($interface->toString(), $interface->getLine())
                     ->addDependency(new ClassDependency($interface->toCodeString(), $interface->getLine()));
            }
        }

        if ($node instanceof Node\Expr\Instanceof_) {
            $this->classDescriptionBuilder
                ->addDependency(new ClassDependency($node->class->toString(), $node->getLine()));
        }

        if ($node instanceof Node\Expr\New_ && !($node->class instanceof Node\Expr\Variable)) {
            $this->classDescriptionBuilder
                ->addDependency(new ClassDependency($node->class->toString(), $node->getLine()));
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
        if ($node instanceof Node\Stmt\Class_) {
            $classDescription = $this->classDescriptionBuilder->get();

            $this->classDescriptions[] = $classDescription;
        }
    }
}
