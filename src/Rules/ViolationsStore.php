<?php
declare(strict_types=1);


namespace Arkitect\Rules;

class ViolationsStore implements \IteratorAggregate, \Countable
{
    /**
     * @var string[]
     */
    private $violations;

    public function __construct(string ...$violations)
    {
        $this->violations = $violations;
    }

    public static function fromViolations(string ...$violations): self
    {
        return new self(...$violations);
    }

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

    public function toArray(): array
    {
        return $this->violations;
    }
}
