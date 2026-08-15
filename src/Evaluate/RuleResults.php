<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

/**
 * @implements \IteratorAggregate<int, RuleResult>
 */
final class RuleResults implements \IteratorAggregate, \Countable
{
    /** @var list<RuleResult> */
    private array $items;

    public function __construct(RuleResult ...$items)
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
