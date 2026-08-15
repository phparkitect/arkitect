<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

use Arkitect\Evaluate\Constraint\Constraint;
use Arkitect\Evaluate\Selector\Selector;

/**
 * A rule being written, before it says anything a run could check.
 *
 * It exists so `Rule` never does: in v1 `Rule::allClasses()` handed back a
 * Rule that had no constraint yet and was mutated into shape, which is a
 * half-built object of exactly the kind a constructor should make
 * impossible. Here the incomplete states have their own types, and the only
 * way to reach a `Rule` is through `because()`.
 *
 * Selectors arrive one at a time — `that()` then `andThat()` — rather than
 * as a list, because the single-selector case is the common one and reads
 * better without brackets.
 */
final class RuleDraft
{
    /** @param list<Selector> $selectors */
    private function __construct(private readonly array $selectors)
    {
    }

    public static function start(): self
    {
        return new self([]);
    }

    public function that(Selector $selector): self
    {
        return new self([...$this->selectors, $selector]);
    }

    public function andThat(Selector $selector): self
    {
        return $this->that($selector);
    }

    public function should(Constraint $constraint): UnexplainedRule
    {
        return new UnexplainedRule($this->selectors, $constraint);
    }
}
