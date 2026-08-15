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
        // the list<NotApplicableClass> in the docblock is a promise to the analyser;
        // this is the one PHP keeps at runtime, since the constructor takes an
        // array rather than a typed variadic (see #599 in ARCHITECTURE.md)
        foreach ($items as $item) {
            if (!$item instanceof NotApplicableClass) {
                throw new \InvalidArgumentException(\sprintf('Expected a not-applicable class, got %s.', get_debug_type($item)));
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
