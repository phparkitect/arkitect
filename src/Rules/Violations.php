<?php

declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Exceptions\IndexNotFoundException;

/**
 * @template-implements \IteratorAggregate<Violation>
 */
class Violations implements \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * @var array<Violation>
     */
    private array $violations;

    public function __construct()
    {
        $this->violations = [];
    }

    public static function fromJson(string $json): self
    {
        $json = json_decode($json, true);

        $instance = new self();

        $instance->violations = array_map(function (array $json): Violation {
            return Violation::fromJson($json);
        }, $json['violations']);

        return $instance;
    }

    public function add(Violation $violation): void
    {
        $this->violations[] = $violation;
    }

    public function merge(self $other): void
    {
        $this->violations = array_merge($this->violations, $other->toArray());
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
        if (!$ignoreBaselineLinenumbers) {
            $this->violations = array_values(array_udiff(
                $this->violations,
                $violations->violations,
                [__CLASS__, 'compareViolations']
            ));

            return;
        }

        $baselineViolations = $violations->violations;
        foreach ($this->violations as $idx => $violation) {
            foreach ($baselineViolations as $baseIdx => $baselineViolation) {
                if (
                    $baselineViolation->getFqcn() === $violation->getFqcn()
                    && $baselineViolation->getError() === $violation->getError()
                ) {
                    unset($this->violations[$idx], $baselineViolations[$baseIdx]);
                    continue 2;
                }
            }
        }

        $this->violations = array_values($this->violations);
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
