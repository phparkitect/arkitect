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
     * Pairing happens in two passes: first the violations still sitting where
     * the baseline recorded them, then, among what is left, the ones matching
     * by class and reported problem alone — an edit above a violation moves it
     * without making it a new one. The line number is therefore never part of
     * the identity of a violation, only a hint on which entry of a group to
     * pair with, so there is nothing for the user to choose here.
     *
     * When a group of identical violations both moved and grew, which of them
     * is reported as new is a guess; how many are is not.
     */
    public function matchAgainst(self $baseline): ViolationsMatch
    {
        [$stillThere, $moved, $paired] = self::pairWith($this->violations, $baseline->violations, [__CLASS__, 'positionKey']);

        $unpairedEntries = array_diff_key($baseline->violations, $paired);

        [$movedAndKnown, $new, $pairedMoved] = self::pairWith($moved, $unpairedEntries, [__CLASS__, 'violationKey']);

        return new ViolationsMatch(
            self::fromArray(array_intersect_key($this->violations, $stillThere + $movedAndKnown)),
            self::fromArray($new),
            self::fromArray(array_diff_key($unpairedEntries, $pairedMoved))
        );
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
     * Pairs each violation with an entry carrying the same key, in the order
     * both appear in their file, and reports what paired with what.
     *
     * @param array<int, Violation>      $violations
     * @param array<int, Violation>      $entries
     * @param callable(Violation):string $key
     *
     * @return array{array<int, true>, array<int, Violation>, array<int, true>} the paired violations, the unpaired ones, the paired entries
     */
    private static function pairWith(array $violations, array $entries, callable $key): array
    {
        $entriesByKey = self::indexBy($entries, $key);

        $matched = [];
        $unpaired = [];
        $paired = [];
        $pairedPerKey = [];

        foreach ($violations as $idx => $violation) {
            $violationKey = $key($violation);
            $alreadyPaired = $pairedPerKey[$violationKey] ?? 0;

            if (!isset($entriesByKey[$violationKey][$alreadyPaired])) {
                $unpaired[$idx] = $violation;

                continue;
            }

            $pairedPerKey[$violationKey] = $alreadyPaired + 1;
            $paired[$entriesByKey[$violationKey][$alreadyPaired]] = true;
            $matched[$idx] = true;
        }

        return [$matched, $unpaired, $paired];
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
