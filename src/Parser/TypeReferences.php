<?php

declare(strict_types=1);

namespace Arkitect\Parser;

/**
 * A typed collection of TypeReference — the only thing a bare array can't
 * do here is stop something other than a TypeReference from ending up in
 * extends/implements/traits/dependencies/attributes. No query methods:
 * those belong wherever the first real need for one shows up.
 *
 * @implements \IteratorAggregate<int, TypeReference>
 */
final class TypeReferences implements \IteratorAggregate, \Countable
{
    /** @var list<TypeReference> */
    private array $items;

    public function __construct(TypeReference ...$items)
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
