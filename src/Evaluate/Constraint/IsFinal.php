<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Constraint;

use Arkitect\Evaluate\Outcome;
use Arkitect\Evaluate\Violation;
use Arkitect\Evaluate\Violations;
use Arkitect\Parser\ClassKind;
use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;

/**
 * An enum satisfies this without carrying the keyword: enums are final, and
 * the parser records the type rather than the syntax.
 */
final class IsFinal implements Constraint
{
    public function evaluate(ParsedClass $class, ClassGraph $classGraph): Outcome
    {
        $impossible = $this->whyImpossible($class);

        if (null !== $impossible) {
            return Outcome::notApplicable($class, $impossible);
        }

        if ($class->isFinal) {
            return new Outcome();
        }

        return new Outcome(new Violations([
            Violation::create($class, self::class, 'is not final'),
        ]));
    }

    /** The language facts that leave the requirement no way to be satisfied. */
    private function whyImpossible(ParsedClass $class): ?string
    {
        return match (true) {
            ClassKind::Interface === $class->kind => 'an interface cannot be final',
            ClassKind::Trait === $class->kind => 'a trait cannot be final',
            $class->isAbstract => 'an abstract class cannot be final',
            default => null,
        };
    }
}
