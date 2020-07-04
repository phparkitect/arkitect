<?php


namespace Arkitect\Analyzer;


use Arkitect\Analyzer\Events\ClassAnalyzed;
use Psr\EventDispatcher\EventDispatcherInterface;

class ClassDescriptionArrayParser implements Parser
{

    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function parse($classDescription): void
    {
        $this->eventDispatcher->dispatch(new ClassAnalyzed($classDescription));
    }


}