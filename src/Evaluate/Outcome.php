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
        public readonly NotApplicableClasses $notApplicable = new NotApplicableClasses(),
    ) {
    }

    public static function unresolved(ParsedClass $class, string $detail): self
    {
        return new self(unresolved: new UnresolvedClasses(UnresolvedClass::create($class, $detail)));
    }

    /**
     * $detail states the language fact that makes the requirement
     * impossible, which is also what keeps this from drifting into a second
     * selector: "an interface cannot be final" is a fact about PHP, while
     * "classes in App\Legacy are exempt" would be the user's intent, and
     * that belongs in a selector.
     */
    public static function notApplicable(ParsedClass $class, string $detail): self
    {
        return new self(notApplicable: new NotApplicableClasses(NotApplicableClass::create($class, $detail)));
    }
}
