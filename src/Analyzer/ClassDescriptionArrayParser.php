<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

use Arkitect\Analyzer\Events\ClassAnalyzed;
use Psr\EventDispatcher\EventDispatcherInterface;

class ClassDescriptionArrayParser implements Parser
{
    private \Psr\EventDispatcher\EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function parse($classDescription): void
    {
        $this->eventDispatcher->dispatch(new ClassAnalyzed($classDescription));
    }

    public function onClassAnalyzed(callable $callable): void
    {
    }
}
