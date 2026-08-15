<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Selector;

/**
 * @implements \IteratorAggregate<int, Selector>
 */
final class Selectors implements \IteratorAggregate, \Countable
{
    /** @var list<Selector> */
    private array $items;

    public function __construct(Selector ...$items)
    {
        $this->items = array_values($items);
    }

    public function with(Selector $selector): self
    {
        return new self(...$this->items, ...[$selector]);
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
