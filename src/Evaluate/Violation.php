<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

use Arkitect\Parser\ParsedClass;
use Arkitect\Parser\TypeReference;

/**
 * Structured, not a rendered sentence: the baseline keys on this data, so
 * nothing downstream has to parse prose back apart. `line` is never null —
 * an expression that references a specific node uses that node's line,
 * anything purely structural falls back to the class's declaration line.
 */
final class Violation
{
    /**
     * @param class-string<Expression> $expression which expression produced this
     * @param string                   $detail     what was wrong, without the class name
     */
    public function __construct(
        public readonly string $fqcn,
        public readonly string $filePath,
        public readonly int $line,
        public readonly string $expression,
        public readonly string $detail,
    ) {
    }

    /**
     * For an expression with no specific node to point at: the violation
     * lands on the class's own declaration line.
     *
     * @param class-string<Expression> $expression
     */
    public static function create(ParsedClass $class, string $expression, string $detail): self
    {
        return new self(
            fqcn: $class->fqcn,
            filePath: $class->filePath,
            line: $class->line,
            expression: $expression,
            detail: $detail,
        );
    }

    /**
     * For an expression that found its problem at one specific referenced
     * type: the violation points at that reference's line rather than at
     * the class, so a class with several bad dependencies reports each one
     * where it actually appears.
     *
     * @param class-string<Expression> $expression
     */
    public static function createAt(
        ParsedClass $class,
        TypeReference $reference,
        string $expression,
        string $detail,
    ): self {
        return new self(
            fqcn: $class->fqcn,
            filePath: $class->filePath,
            line: $reference->line,
            expression: $expression,
            detail: $detail,
        );
    }
}
