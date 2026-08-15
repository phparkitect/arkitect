<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

use Arkitect\Evaluate\Constraint\Constraint;
use Arkitect\Parser\Fqcn;
use Arkitect\Parser\ParsedClass;
use Arkitect\Parser\TypeReference;

/**
 * Structured, not a rendered sentence: the baseline keys on this data, so
 * nothing downstream has to parse prose back apart. `line` is never null —
 * a constraint that references a specific node uses that node's line,
 * anything purely structural falls back to the class's declaration line.
 */
final class Violation
{
    /** @var class-string<Constraint> */
    public readonly string $constraint;

    /**
     * @param class-string<Constraint> $constraint which constraint produced this
     * @param string                   $detail     what was wrong, without the class name
     */
    private function __construct(
        public readonly string $fqcn,
        public readonly string $filePath,
        public readonly int $line,
        string $constraint,
        public readonly string $detail,
    ) {
        // the location comes from an already-valid ParsedClass or
        // TypeReference; these two are what a caller still chooses
        $this->constraint = (new Fqcn($constraint))->toString();

        if ('' === trim($detail)) {
            throw new \InvalidArgumentException('A violation has to say what was wrong.');
        }
    }

    /**
     * For a constraint with no specific node to point at: the violation
     * lands on the class's own declaration line.
     *
     * @param class-string<Constraint> $constraint
     */
    public static function create(ParsedClass $class, string $constraint, string $detail): self
    {
        return new self(
            fqcn: $class->fqcn,
            filePath: $class->filePath,
            line: $class->line,
            constraint: $constraint,
            detail: $detail,
        );
    }

    /**
     * For a constraint that found its problem at one specific referenced
     * type: the violation points at that reference's line rather than at
     * the class, so a class with several bad dependencies reports each one
     * where it actually appears.
     *
     * @param class-string<Constraint> $constraint
     */
    public static function createAt(
        ParsedClass $class,
        TypeReference $reference,
        string $constraint,
        string $detail,
    ): self {
        return new self(
            fqcn: $class->fqcn,
            filePath: $class->filePath,
            line: $reference->line,
            constraint: $constraint,
            detail: $detail,
        );
    }
}
