<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

/**
 * @implements \IteratorAggregate<int, NotApplicableClass>
 */
final class NotApplicableClasses implements \IteratorAggregate, \Countable
{
    /** @var list<NotApplicableClass> */
    private array $items;

    /** @param list<NotApplicableClass> $items */
    public function __construct(array $items = [])
    {
        $this->items = array_values($items);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return \count($this->items);
    }
}
