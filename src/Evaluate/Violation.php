<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

use Arkitect\Evaluate\Constraint\Constraint;
use Arkitect\Parser\Fqcn;
use Arkitect\Parser\ParsedClass;
use Arkitect\Parser\TypeReference;

/**
 * Structured, not a rendered sentence. `detail` is prose for a human and
 * may be reworded at any time. `key` is what the violation is about beyond
 * the class — the forbidden dependency, the interface that is missing, the
 * namespace expected — and is what identity is built from, so rewording a
 * message cannot invalidate a baseline. It must come from the rule's own
 * parameters, never from the message.
 *
 * `line` is never null: a constraint pointing at a specific node uses that
 * node's line, anything structural falls back to the class's own.
 */
final class Violation
{
    /** @var class-string<Constraint> */
    public readonly string $constraint;

    /**
     * @param class-string<Constraint> $constraint which constraint produced this
     * @param string                   $detail     what was wrong, for a human to read
     * @param ?string                  $key    what it was about, for identity
     */
    private function __construct(
        public readonly string $fqcn,
        public readonly string $filePath,
        public readonly int $line,
        string $constraint,
        public readonly string $detail,
        public readonly ?string $key = null,
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
    public static function create(
        ParsedClass $class,
        string $constraint,
        string $detail,
        ?string $key = null,
    ): self {
        return new self(
            fqcn: $class->fqcn,
            filePath: $class->filePath,
            line: $class->line,
            constraint: $constraint,
            detail: $detail,
            key: $key,
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
            // the referenced name is what this violation is about, and the
            // only thing that tells two of them on one class apart
            key: $reference->name,
        );
    }
}
