<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;

final class IsFinal implements Expression
{
    public function evaluate(ParsedClass $class, ClassGraph $classGraph): Violations
    {
        if ($class->isFinal) {
            return new Violations();
        }

        return new Violations([
            Violation::create($class, self::class, 'is not final'),
        ]);
    }
}
