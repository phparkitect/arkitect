<?php

declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Exceptions\FailOnFirstViolationException;
use Arkitect\Exceptions\IndexNotFoundException;

class Violations implements \IteratorAggregate, \Countable
{
    /**
     * @var Violation[]
     */
    private $violations;
    /**
     * @var bool
     */
    private $stopOnFailure;

    public function __construct(bool $stopOnFailure = false)
    {
        $this->violations = [];
        $this->stopOnFailure = $stopOnFailure;
    }

    public function add(Violation $violation): void
    {
        $this->violations[] = $violation;
        if ($this->stopOnFailure) {
            throw new FailOnFirstViolationException();
        }
    }

    public function get(int $index): Violation
    {
        if (!\array_key_exists($index, $this->violations)) {
            throw new IndexNotFoundException($index);
        }

        return $this->violations[$index];
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->violations as $violation) {
            yield $violation;
        }
    }

    public function count(): int
    {
        return \count($this->violations);
    }

    public function groupedByFqcn(): array
    {
        return array_reduce($this->violations, function (array $accumulator, Violation $element) {
            $accumulator[$element->getFqcn()][] = $element;

            return $accumulator;
        }, []);
    }

    public function toString(): string
    {
        $errors = '';
        $violationsCollection = $this->groupedByFqcn();

        /**
         * @var string      $key
         * @var Violation[] $violationsByFqcn
         */
        foreach ($violationsCollection as $key => $violationsByFqcn) {
            $violationForThisFqcn = \count($violationsByFqcn);
            $errors .= "\n$key has {$violationForThisFqcn} violations";

            foreach ($violationsByFqcn as $violation) {
                $errors .= "\n  ".$violation->getError();

                if (null !== $violation->getLine()) {
                    $errors .= ' (on line '.$violation->getLine().')';
                }
            }
            $errors .= "\n";
        }

        return $errors;
    }

    public function toArray(): array
    {
        return $this->violations;
    }
}
