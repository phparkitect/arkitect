<?php
declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Rules\ParsingErrors;
use Arkitect\Rules\UnusedRules;
use Arkitect\Rules\Violations;

class AnalysisResult
{
    private Violations $violations;

    private ParsingErrors $parsingErrors;

    private UnusedRules $unusedRules;

    public function __construct(Violations $violations, ParsingErrors $parsingErrors, ?UnusedRules $unusedRules = null)
    {
        $this->violations = $violations;
        $this->parsingErrors = $parsingErrors;
        $this->unusedRules = $unusedRules ?? new UnusedRules();
    }

    public function getViolations(): Violations
    {
        return $this->violations;
    }

    public function getParsingErrors(): ParsingErrors
    {
        return $this->parsingErrors;
    }

    public function getUnusedRules(): UnusedRules
    {
        return $this->unusedRules;
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

    public function hasUnusedRules(): bool
    {
        return $this->unusedRules->count() > 0;
    }
}
