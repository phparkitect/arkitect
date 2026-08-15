<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Constraint;

use Arkitect\Evaluate\Outcome;
use Arkitect\Evaluate\Violation;
use Arkitect\Evaluate\Violations;
use Arkitect\Parser\ClassKind;
use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;

final class IsAbstract implements Constraint
{
    public function evaluate(ParsedClass $class, ClassGraph $classGraph): Outcome
    {
        $impossible = $this->whyImpossible($class);

        if (null !== $impossible) {
            return Outcome::notApplicable($class, $impossible);
        }

        if ($class->isAbstract) {
            return new Outcome();
        }

        return new Outcome(new Violations(
            Violation::create($class, self::class, 'is not abstract'),
        ));
    }

    /** The language facts that leave the requirement no way to be satisfied. */
    private function whyImpossible(ParsedClass $class): ?string
    {
        return match (true) {
            ClassKind::Interface === $class->kind => 'an interface is already abstract',
            ClassKind::Trait === $class->kind => 'a trait cannot be abstract',
            ClassKind::Enum === $class->kind => 'an enum cannot be abstract',
            $class->isFinal => 'a final class cannot be abstract',
            default => null,
        };
    }
}
