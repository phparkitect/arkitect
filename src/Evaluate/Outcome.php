<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

use Arkitect\Parser\ParsedClass;

/**
 * What a constraint has to say about one class: what it found wrong, and
 * what it couldn't determine. Two channels rather than one, because "your
 * code breaks this rule" and "I couldn't tell" are different claims and
 * only the first belongs in a baseline.
 */
final class Outcome
{
    public function __construct(
        public readonly Violations $violations = new Violations(),
        public readonly UnresolvedClasses $unresolved = new UnresolvedClasses(),
    ) {
    }

    public static function unresolved(ParsedClass $class, string $detail): self
    {
        return new self(unresolved: new UnresolvedClasses([UnresolvedClass::create($class, $detail)]));
    }
}
