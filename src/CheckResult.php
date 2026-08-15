<?php

declare(strict_types=1);

namespace Arkitect;

use Arkitect\Evaluate\RuleResults;
use Arkitect\Parser\ParsingErrors;

final class CheckResult
{
    public function __construct(
        public readonly int $classesChecked,
        public readonly ParsingErrors $parsingErrors,
        public readonly RuleResults $ruleResults,
    ) {
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
