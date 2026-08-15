<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

/**
 * Carries how many classes the rule actually looked at, not just what it
 * found. A rule whose selectors match nothing produces no violations, and
 * so does a rule the whole codebase satisfies — the report has to tell
 * those two apart, and this is the only place the difference exists.
 *
 * `selected` and `checked` differ when a constraint couldn't mean anything
 * for some of the classes picked, which is why both are here rather than
 * one number: they fail in different ways and are fixed differently.
 */
final class RuleResult
{
    public function __construct(
        /** The rule's own reason, so a report can title the group without pairing objects up again. */
        public readonly string $because,
        public readonly int $selected,
        public readonly int $checked,
        public readonly Violations $violations,
        public readonly UnresolvedClasses $unresolved = new UnresolvedClasses(),
        public readonly NotApplicableClasses $notApplicable = new NotApplicableClasses(),
    ) {
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
