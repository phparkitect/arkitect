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

    /** @param list<Violation> $items */
    public function __construct(array $items = [])
    {
        // the list<Violation> in the docblock is a promise to the analyser;
        // this is the one PHP keeps at runtime, since the constructor takes an
        // array rather than a typed variadic (see #599 in ARCHITECTURE.md)
        foreach ($items as $item) {
            if (!$item instanceof Violation) {
                throw new \InvalidArgumentException(\sprintf('Expected a violation, got %s.', get_debug_type($item)));
            }
        }

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
