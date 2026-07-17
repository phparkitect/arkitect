<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Rules\Violations;

/**
 * The set of known violations to be ignored by a check run.
 *
 * This is a pure domain object: loading and saving baselines from disk
 * lives in BaselineFileRepository.
 */
class Baseline
{
    private Violations $violations;

    private int $staleViolationsCount = 0;

    private function __construct(Violations $violations)
    {
        $this->violations = $violations;
    }

    public static function fromViolations(Violations $violations): self
    {
        return new self($violations);
    }

    public static function empty(): self
    {
        return new self(new Violations());
    }

    public function getViolations(): Violations
    {
        return $this->violations;
    }

    public function applyTo(Violations $violations, bool $ignoreBaselineLinenumbers): void
    {
        $this->staleViolationsCount = $this->violations->countUnmatchedIn($violations, $ignoreBaselineLinenumbers);

        $violations->remove($this->violations, $ignoreBaselineLinenumbers);
    }

    /**
     * Number of baseline entries that no longer match any current violation,
     * i.e. that have already been fixed and could be removed from the baseline.
     * Only meaningful after applyTo() has run.
     */
    public function getStaleViolationsCount(): int
    {
        return $this->staleViolationsCount;
    }

    /**
     * Shrink-only update: returns a baseline containing only the entries that
     * still match a current violation — nothing is ever added. Matching
     * ignores line numbers and the current violations are the ones kept, so
     * pruning also refreshes line numbers gone stale after refactorings.
     */
    public function prune(Violations $currentViolations): self
    {
        $prunedViolations = $currentViolations->intersection($this->violations);

        // a baseline stored without line numbers keeps its format
        if (!$this->hasLineNumbers()) {
            $prunedViolations = $prunedViolations->withoutLineNumbers();
        }

        return new self($prunedViolations);
    }

    public function withoutLineNumbers(): self
    {
        return new self($this->violations->withoutLineNumbers());
    }

    private function hasLineNumbers(): bool
    {
        foreach ($this->violations as $violation) {
            if (null !== $violation->getLine()) {
                return true;
            }
        }

        return false;
    }
}
