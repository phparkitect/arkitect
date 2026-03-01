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

        $instance->violations = array_map(static fn (array $json): Violation => Violation::fromJson($json), $json['violations']);

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
        return array_reduce($this->violations, static function (array $accumulator, Violation $element) {
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
                    && self::extractViolationKey($baselineViolation->getError()) === self::extractViolationKey($violation->getError())
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
        usort($this->violations, static fn (Violation $v1, Violation $v2): int => $v1 <=> $v2);
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    /**
     * Comparison method that respects all fields in the violation.
     *
     * Uses the stable violation key (the part before ', but ') for comparison,
     * so that changes to rule configuration (e.g. allowed namespaces) do not
     * invalidate existing baseline entries.
     */
    public static function compareViolations(Violation $a, Violation $b): int
    {
        return [
            $a->getFqcn(),
            $a->getLine(),
            $a->getFilePath(),
            self::extractViolationKey($a->getError()),
        ] <=> [
            $b->getFqcn(),
            $b->getLine(),
            $b->getFilePath(),
            self::extractViolationKey($b->getError()),
        ];
    }

    /**
     * Extracts the stable violation-specific part from an error message.
     *
     * ViolationMessage produces two formats:
     * - withDescription: "$violation, but $ruleDescription" → returns $violation
     * - selfExplanatory: "$ruleDescription" (no ", but ") → returns the full string
     *
     * The rule description may include configuration-dependent values (like namespace lists)
     * that change when the rule config is updated. The violation part is always stable.
     */
    private static function extractViolationKey(string $error): string
    {
        $pos = strpos($error, ', but ');
        if (false !== $pos) {
            return substr($error, 0, $pos);
        }

        return $error;
    }
}
