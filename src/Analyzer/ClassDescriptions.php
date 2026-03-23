<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

/**
 * @template-implements \IteratorAggregate<ClassDescription>
 */
class ClassDescriptions implements \IteratorAggregate, \Countable
{
    /** @var array<ClassDescription> */
    private $classDescriptions;

    /**
     * @param array<ClassDescription> $classDescriptions
     */
    public function __construct(array $classDescriptions = [])
    {
        $this->classDescriptions = $classDescriptions;
    }

    public function add(ClassDescription $classDescription): void
    {
        $this->classDescriptions[] = $classDescription;
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->classDescriptions as $classDescription) {
            yield $classDescription;
        }
    }

    public function count(): int
    {
        return \count($this->classDescriptions);
    }

    /**
     * @return array<ClassDescription>
     */
    public function toArray(): array
    {
        return $this->classDescriptions;
    }
}
