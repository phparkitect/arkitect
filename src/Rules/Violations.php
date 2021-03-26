<?php
declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Exceptions\IndexNotFoundException;

class Violations implements \IteratorAggregate, \Countable
{
    /**
     * @var Violation[]
     */
    private $violations;

    public function __construct(array $violations = [])
    {
        $this->violations = $violations;
    }

    public function add(Violation $violation): void
    {
        $this->violations[] = $violation;
    }

    public function get(int $index): Violation
    {
        if (!\array_key_exists($index, $this->violations)) {
            throw new IndexNotFoundException($index);
        }

        return $this->violations[$index];
    }

    public function getIterator()
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
            $errors .= "\n".$key.' violates rules';

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
