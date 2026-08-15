<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

use Arkitect\Parser\ParsedClass;

/**
 * A class whose constraint could not mean anything for it: an interface
 * cannot be final, an abstract class cannot be final either. Not a
 * violation, because there is no edit that would satisfy the rule, and not
 * an unresolved class, because nothing is missing — PHP simply doesn't
 * allow the thing being asked for.
 *
 * Carried rather than printed. A user who writes "domain objects must be
 * final" means the classes, and is not surprised that the interface next to
 * them was skipped — saying so on every run is noise about something they
 * cannot act on. It matters in one case, which `RuleResult` answers: a rule
 * that could judge *nothing* protects nothing while looking like it does.
 */
final class NotApplicableClass
{
    public function __construct(
        public readonly string $fqcn,
        public readonly string $filePath,
        public readonly int $line,
        public readonly string $detail,
    ) {
    }

    public static function create(ParsedClass $class, string $detail): self
    {
        return new self(
            fqcn: $class->fqcn,
            filePath: $class->filePath,
            line: $class->line,
            detail: $detail,
        );
    }
}
