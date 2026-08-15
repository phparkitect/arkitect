<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Constraint;

use Arkitect\Evaluate\Violation;
use Arkitect\Evaluate\Violations;
use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;

final class IsAbstract implements Constraint
{
    public function evaluate(ParsedClass $class, ClassGraph $classGraph): Violations
    {
        if ($class->isAbstract) {
            return new Violations();
        }

        return new Violations([
            Violation::create($class, self::class, 'is not abstract'),
        ]);
    }
}
