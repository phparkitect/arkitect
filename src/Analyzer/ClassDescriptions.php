<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

/**
 * @template-implements \IteratorAggregate<ClassDescription>
 * @template-implements \ArrayAccess<int, ClassDescription>
 */
class ClassDescriptions implements \IteratorAggregate, \Countable, \ArrayAccess
{
    /** @var array<ClassDescription> */
    private array $classDescriptions;

    /**
     * @param array<ClassDescription> $classDescriptions
     */
    public function __construct(array $classDescriptions = [])
    {
        $this->classDescriptions = $classDescriptions;
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

    /** @param int $offset */
    public function offsetExists($offset): bool
    {
        return \array_key_exists($offset, $this->classDescriptions);
    }

    /**
     * @param int $offset
     *
     * @return ClassDescription
     */
    public function offsetGet($offset): mixed
    {
        return $this->classDescriptions[$offset];
    }

    /**
     * @param int|null         $offset
     * @param ClassDescription $value
     */
    public function offsetSet($offset, $value): void
    {
        if (null === $offset) {
            $this->classDescriptions[] = $value;
        } else {
            $this->classDescriptions[$offset] = $value;
        }
    }

    /** @param int $offset */
    public function offsetUnset($offset): void
    {
        unset($this->classDescriptions[$offset]);
    }
}
