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

    /**
     * Filters out of $violations the ones known to the baseline, leaving the
     * given set untouched: what is left to report is in the returned result,
     * together with the number of baseline entries nothing matched.
     */
    public function applyTo(Violations $violations): BaselineResult
    {
        $match = $violations->matchAgainst($this->violations);

        return new BaselineResult($match->new(), $match->stale()->count());
    }

    /**
     * Shrink-only update: returns a baseline containing only the entries that
     * still match a current violation — nothing is ever added. The current
     * violations are the ones kept, so pruning also refreshes line numbers
     * gone stale after refactorings.
     */
    public function prune(Violations $currentViolations): self
    {
        $prunedViolations = $currentViolations->matchAgainst($this->violations)->known();

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
