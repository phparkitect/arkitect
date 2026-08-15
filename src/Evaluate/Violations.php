<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

/**
 * Immutable: `evaluate()` returns one instead of mutating an accumulator
 * passed in, which is what made this possible.
 *
 * @implements \IteratorAggregate<int, Violation>
 */
final class Violations implements \IteratorAggregate, \Countable
{
    /** @var list<Violation> */
    private array $items;

    public function __construct(Violation ...$items)
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
