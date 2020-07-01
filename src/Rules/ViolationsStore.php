<?php


namespace Arkitect\Rules;

class ViolationsStore implements \IteratorAggregate, \Countable
{
    private $violations = [];

    public function add(string $violation): void
    {
        $this->violations[] = $violation;
    }

    public function get(int $index): string
    {
        return $this->violations[$index] ?? '';
    }

    public function getIterator()
    {
        foreach ($this->violations as $violation) {
            yield $violation;
        }
    }

    public function count(): int
    {
        return count($this->violations);
    }

    public function toString(): string
    {
        return implode("\n", $this->violations);
    }
}