<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Rules\Violations;

/**
 * The outcome of applying a Baseline to the violations of a run: what is
 * left to report, and how many baseline entries went unused.
 */
class BaselineResult
{
    public function __construct(
        private Violations $remainingViolations,
        private int $staleBaselineEntriesCount,
    ) {
    }

    public function getRemainingViolations(): Violations
    {
        return $this->remainingViolations;
    }

    /**
     * Number of baseline entries that no longer match any current violation,
     * i.e. that have already been fixed and could be removed from the baseline.
     */
    public function getStaleBaselineEntriesCount(): int
    {
        return $this->staleBaselineEntriesCount;
    }
}
