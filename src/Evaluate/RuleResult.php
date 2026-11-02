<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

/**
 * What a rule found, and how much it actually looked at — a rule that
 * matched nothing and a rule the codebase satisfies both report no
 * violations, and only these counts tell them apart.
 *
 * `selected` and `checked` differ when a constraint could not mean anything
 * for some of the classes picked.
 */
final class RuleResult
{
    /** @param list<string> $missingExclusions names except() was given that no class answers to */
    public function __construct(
        public readonly string $because,
        public readonly int $selected,
        public readonly int $checked,
        public readonly Violations $violations,
        public readonly UnresolvedClasses $unresolved = new UnresolvedClasses(),
        public readonly NotApplicableClasses $notApplicable = new NotApplicableClasses(),
        public readonly array $missingExclusions = [],
    ) {
    }

    /**
     * The same result with its violations replaced, which is how `Check`
     * applies the baseline. It exists so that filtering cannot quietly drop
     * one of the other channels by rebuilding the object without it.
     */
    public function withViolations(Violations $violations): self
    {
        return new self(
            $this->because,
            $this->selected,
            $this->checked,
            $violations,
            $this->unresolved,
            $this->notApplicable,
            $this->missingExclusions,
        );
    }

    /** The selectors picked nothing: the `that()` is the thing to fix. */
    public function matchedNothing(): bool
    {
        return 0 === $this->selected;
    }

    /**
     * Classes were picked and none could be judged — a rule that reads as
     * protecting something while protecting nothing. The `should()` is the
     * thing to fix, which is why this is not the same signal as above.
     */
    public function judgedNothing(): bool
    {
        return $this->selected > 0 && 0 === $this->checked;
    }

    /**
     * A rule with unresolved classes has not passed, even with no
     * violations: it was unable to look at part of what it was asked about.
     */
    public function isConclusive(): bool
    {
        return 0 === \count($this->unresolved);
    }
}
