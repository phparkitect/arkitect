<?php

declare(strict_types=1);

namespace Arkitect\Parser;

/**
 * @implements \IteratorAggregate<int, ParsingError>
 */
final class ParsingErrors implements \IteratorAggregate, \Countable
{
    /** @var list<ParsingError> */
    private array $items;

    public function __construct(ParsingError ...$items)
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
