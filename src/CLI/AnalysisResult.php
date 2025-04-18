<?php
declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Rules\ParsingErrors;
use Arkitect\Rules\Violations;

class AnalysisResult
{
    private Violations $violations;

    private ParsingErrors $parsingErrors;

    public function __construct(Violations $violations, ParsingErrors $parsingErrors)
    {
        $this->violations = $violations;
        $this->parsingErrors = $parsingErrors;
    }

    public function getViolations(): Violations
    {
        return $this->violations;
    }

    public function getParsingErrors(): ParsingErrors
    {
        return $this->parsingErrors;
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
