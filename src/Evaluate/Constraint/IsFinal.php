<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Constraint;

use Arkitect\Evaluate\Outcome;
use Arkitect\Evaluate\Violation;
use Arkitect\Evaluate\Violations;
use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;

final class IsFinal implements Constraint
{
    public function evaluate(ParsedClass $class, ClassGraph $classGraph): Outcome
    {
        if ($class->isFinal) {
            return new Outcome();
        }

        return new Outcome(new Violations([
            Violation::create($class, self::class, 'is not final'),
        ]));
    }
}
