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
     * Pairs the violations of this set (the current run) with the entries of
     * $baseline, one to one: what matched, what is new and what the baseline
     * still claims but nothing matches anymore.
     *
     * A violation is identified by its class and by the problem it reports;
     * $ignoreLineNumbers decides whether where it sits in the file is part of
     * that identity too.
     */
    public function matchAgainst(self $baseline, bool $ignoreLineNumbers): ViolationsMatch
    {
        $key = $ignoreLineNumbers ? [__CLASS__, 'violationKey'] : [__CLASS__, 'positionKey'];

        $currentViolations = $this->violations;
        $baselineViolations = $baseline->violations;
        $baselineByKey = self::indexBy($baselineViolations, $key);

        foreach ($currentViolations as $idx => $violation) {
            $violationKey = $key($violation);
            $unpaired = $baselineByKey[$violationKey] ?? [];

            if ([] === $unpaired) {
                continue;
            }

            $baselineIdx = array_shift($unpaired);
            $baselineByKey[$violationKey] = $unpaired;

            unset($currentViolations[$idx], $baselineViolations[$baselineIdx]);
        }

        return new ViolationsMatch(
            self::fromArray(array_diff_key($this->violations, $currentViolations)),
            self::fromArray($currentViolations),
            self::fromArray($baselineViolations)
        );
    }

    /**
     * @param Violations $violations                Known violations from the baseline
     * @param bool       $ignoreBaselineLinenumbers If set to true, violations from the baseline are ignored for the same file even if the line number is different
     */
    public function remove(self $violations, bool $ignoreBaselineLinenumbers = false): void
    {
        $this->violations = $this->matchAgainst($violations, $ignoreBaselineLinenumbers)->new()->violations;
    }

    /**
     * Counts how many violations in this set (typically the baseline) have no
     * corresponding entry in $current (typically the current run's violations) —
     * i.e. how many are stale and could be removed from the baseline.
     */
    public function countUnmatchedIn(self $current, bool $ignoreLineNumbers): int
    {
        return $current->matchAgainst($this, $ignoreLineNumbers)->stale()->count();
    }

    /**
     * Returns a new set with the violations from this set (typically the
     * current run) that have a matching entry in $other (typically the
     * baseline). Matching uses the stable violation key and ignores line
     * numbers, so the returned violations carry this set's line numbers.
     * Each entry in $other matches at most one violation, so duplicated
     * violations are kept only as many times as they appear in $other.
     */
    public function intersection(self $other): self
    {
        return $this->matchAgainst($other, true)->known();
    }

    public function withoutLineNumbers(): self
    {
        $copy = new self();
        foreach ($this->violations as $violation) {
            $copy->add($violation->withoutLineNumber());
        }

        return $copy;
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
     * Groups the given violations by the key the callback derives from each
     * of them, so that only the ones that can possibly match are compared.
     *
     * @param array<Violation>           $violations
     * @param callable(Violation):string $key
     *
     * @return array<string, array<int>> the indexes of $violations, by key
     */
    private static function indexBy(array $violations, callable $key): array
    {
        $indexes = [];

        foreach ($violations as $idx => $violation) {
            $indexes[$key($violation)][] = $idx;
        }

        return $indexes;
    }

    /**
     * Identifies the problem a violation reports, no matter where in the file
     * it sits: same class, same violation.
     */
    private static function violationKey(Violation $violation): string
    {
        return $violation->getFqcn()."\0".self::extractViolationKey($violation->getError());
    }

    /**
     * Identifies a violation down to the exact spot it was reported at.
     */
    private static function positionKey(Violation $violation): string
    {
        return self::violationKey($violation)."\0".$violation->getFilePath()."\0".(string) $violation->getLine();
    }

    /**
     * @param array<Violation> $violations
     */
    private static function fromArray(array $violations): self
    {
        $instance = new self();
        $instance->violations = array_values($violations);

        return $instance;
    }

    /**
     * Extracts the stable violation-specific part from an error message.
     *
     * ViolationMessage produces two formats:
     * - withDescription: "$violation, but $ruleDescription" → returns $violation
     * - selfExplanatory: "$ruleDescription" (no ", but ") → returns $ruleDescription without its trailing " because $because"
     *
     * The rule description may include configuration-dependent values (like namespace lists),
     * and the because() reason is free text — both change when the config is reworded.
     * The violation part is always stable.
     */
    private static function extractViolationKey(string $error): string
    {
        $pos = strpos($error, ', but ');
        if (false !== $pos) {
            return substr($error, 0, $pos);
        }

        $becausePos = strpos($error, ' because ');
        if (false !== $becausePos) {
            return substr($error, 0, $becausePos);
        }

        return $error;
    }
}
