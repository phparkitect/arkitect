<?php

declare(strict_types=1);

namespace Arkitect\Command;

use Arkitect\Evaluate\RuleResults;
use Arkitect\Evaluate\Violations;
use Arkitect\Parser\ParsingErrors;

final class CheckResult
{
    public function __construct(
        public readonly int $classesChecked,
        public readonly ParsingErrors $parsingErrors,
        public readonly RuleResults $ruleResults,
        public readonly int $baselined = 0,
    ) {
    }

    /** Every violation the run found, with the rules they came from flattened away. */
    public function allViolations(): Violations
    {
        $violations = [];

        foreach ($this->ruleResults as $result) {
            foreach ($result->violations as $violation) {
                $violations[] = $violation;
            }
        }

        return new Violations(...$violations);
    }

    /**
     * A file that could not be parsed counts against the run for the same
     * reason an unresolved class does: something went unlooked-at, and a
     * green result would say otherwise.
     */
    public function isClean(): bool
    {
        if (0 !== \count($this->parsingErrors)) {
            return false;
        }

        foreach ($this->ruleResults as $result) {
            if (0 !== \count($result->violations) || !$result->isConclusive()) {
                return false;
            }
        }

        return true;
    }
}
