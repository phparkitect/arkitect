<?php
declare(strict_types=1);


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

    public function parse($classDescription, array $excludedFiles = []): void
    {
        $file = $classDescription->getFQCN();

        if (!in_array($file, $excludedFiles)) {
            $this->eventDispatcher->dispatch(new ClassAnalyzed($classDescription));
        }
    }
}
