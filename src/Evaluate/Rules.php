<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

/**
 * @implements \IteratorAggregate<int, Rule>
 */
final class Rules implements \IteratorAggregate, \Countable
{
    /** @var list<Rule> */
    private array $items;

    public function __construct(Rule ...$items)
    {
        $this->items = array_values($items);
    }

    public function with(Rule ...$rules): self
    {
        return new self(...$this->items, ...$rules);
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
