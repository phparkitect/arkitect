<?php
declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Analyzer\ParsingErrors;
use Arkitect\Rules\Violations;

class AnalysisResult
{
    private Violations $violations;

    private ParsingErrors $parsingErrors;

    private int $staleBaselineEntriesCount;

    public function __construct(Violations $violations, ParsingErrors $parsingErrors, int $staleBaselineEntriesCount = 0)
    {
        $this->violations = $violations;
        $this->parsingErrors = $parsingErrors;
        $this->staleBaselineEntriesCount = $staleBaselineEntriesCount;
    }

    public function getViolations(): Violations
    {
        return $this->violations;
    }

    public function getParsingErrors(): ParsingErrors
    {
        return $this->parsingErrors;
    }

    /**
     * Number of baseline entries that no longer match any current violation,
     * i.e. that have already been fixed and could be removed from the baseline.
     */
    public function getStaleBaselineEntriesCount(): int
    {
        return $this->staleBaselineEntriesCount;
    }

    public function hasErrors(): bool
    {
        return $this->hasViolations() || $this->hasParsingErrors();
    }

    public function hasViolations(): bool
    {
        return $this->violations->count() > 0;
    }

    public function hasParsingErrors(): bool
    {
        return $this->parsingErrors->count() > 0;
    }
}
