<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

use Arkitect\Parser\ParsedClass;

/**
 * A class the run could not decide about, because resolving it needed an
 * ancestor that was never parsed.
 *
 * Deliberately not a `Violation`. A violation says the code breaks a rule;
 * this says the tool couldn't tell, which is a problem with the input — the
 * same family as `ParsingError`, and like it, collected rather than thrown
 * so one bad class doesn't cost the report on every other. Keeping them
 * apart also keeps the baseline honest: a baseline keys on violations, so
 * folding these in would let an incomplete parse scope be accepted once and
 * then never mentioned again.
 */
final class UnresolvedClass
{
    /**
     * Why there is ever one of these, which is the same answer every time:
     * it is what the type means, not something an instance carries. Each
     * report places it where that report needs it — once in a header for a
     * reader, on every entry for a tool that shows one at a time.
     */
    public const REASON = 'a class it inherits from was not among the files read';

    private function __construct(
        public readonly string $fqcn,
        public readonly string $filePath,
        public readonly int $line,
        public readonly string $detail,
    ) {
        if ('' === trim($detail)) {
            throw new \InvalidArgumentException('An unresolved class has to say why it could not be decided.');
        }
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
