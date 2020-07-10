<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

use Arkitect\Analyzer\Events\ClassAnalyzed;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Psr\EventDispatcher\EventDispatcherInterface;

class FileVisitor extends NodeVisitorAbstract
{
    private $eventDispatcher;

    private $fileCurrentlyAnalized;

    private $classDescriptionBuilder;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function setCurrentAnalisedFile(string $filePath): void
    {
        $this->fileCurrentlyAnalized = $filePath;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            /** @psalm-suppress UndefinedPropertyFetch */
            $this->classDescriptionBuilder = ClassDescriptionBuilder::create(
                $node->namespacedName->toCodeString(),
                $this->fileCurrentlyAnalized
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

        if ($node instanceof Node\Expr\New_) {
            $this->classDescriptionBuilder
                ->addDependency(new ClassDependency($node->class->toString(), $node->getLine()));
        }
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            $classDescription = $this->classDescriptionBuilder->get();

            $this->eventDispatcher->dispatch(new ClassAnalyzed($classDescription));
        }
    }
}
