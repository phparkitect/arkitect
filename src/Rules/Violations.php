<?php

declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Exceptions\FailOnFirstViolationException;
use Arkitect\Exceptions\IndexNotFoundException;
use Arkitect\Shared\String\IndentationHelper;

class Violations implements \IteratorAggregate, \Countable, \JsonSerializable
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

    public static function fromJson(string $json): self
    {
        $json = json_decode($json, true);

        $instance = new self($json['stopOnFailure']);

        $instance->violations = array_map(function (array $json): Violation {
            return Violation::fromJson($json);
        }, $json['violations']);

        return $instance;
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
            $errors .= "\n$key has {$violationForThisFqcn} violations\n";

            $violationDescription = '';
            foreach ($violationsByFqcn as $violation) {
                $violationDescription .= "\n";
                $violationDescription .= $violation->getError();

                if (null !== $violation->getLine()) {
                    $violationDescription .= ' (on line '.$violation->getLine().')';
                }
                $violationDescription .= "\n";
            }
            $errors .= IndentationHelper::indent(trim($violationDescription))."\n";
        }

        return IndentationHelper::clearEmptyLines($errors);
    }

    public function toArray(): array
    {
        return $this->violations;
    }

    /**
     * @param Violations $violations                Known violations from the baseline
     * @param bool       $ignoreBaselineLinenumbers If set to true, violations from the baseline are ignored for the same file even if the line number is different
     */
    public function remove(self $violations, bool $ignoreBaselineLinenumbers = false): void
    {
        $comparisonFunction = [__CLASS__, $ignoreBaselineLinenumbers ? 'compareViolationsIgnoreLineNumber' : 'compareViolations'];

        $this->violations = array_values(array_udiff(
            $this->violations,
            $violations->violations,
            $comparisonFunction
        ));
    }

    public function sort(): void
    {
        usort($this->violations, static function (Violation $v1, Violation $v2): int {
            return $v1 <=> $v2;
        });
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    /**
     * Comparison method that respects all fields in the violation.
     */
    public static function compareViolations(Violation $a, Violation $b): int
    {
        return $a <=> $b;
    }

    /**
     * Comparison method that only checks the namespace and error but ignores the line number.
     */
    public static function compareViolationsIgnoreLineNumber(Violation $a, Violation $b): int
    {
        if (($a->getFqcn() === $b->getFqcn()) && ($a->getError() === $b->getError())) {
            return 0;
        }

        return self::compareViolations($a, $b);
    }
}
