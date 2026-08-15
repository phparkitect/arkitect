<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

/**
 * Carries how many classes the rule actually looked at, not just what it
 * found. A rule whose selectors match nothing produces no violations, and
 * so does a rule the whole codebase satisfies — the report has to tell
 * those two apart, and this is the only place the difference exists.
 */
final class RuleResult
{
    public function __construct(
        public readonly int $checked,
        public readonly Violations $violations,
        public readonly UnresolvedClasses $unresolved = new UnresolvedClasses(),
    ) {
    }

    public function matchedNothing(): bool
    {
        return 0 === $this->checked;
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
