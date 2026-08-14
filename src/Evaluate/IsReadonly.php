<?php

declare(strict_types=1);

namespace Arkitect\Evaluate;

use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;

final class IsReadonly implements Expression
{
    public function evaluate(ParsedClass $class, ClassGraph $classGraph): Violations
    {
        if ($class->isReadonly) {
            return new Violations();
        }

        return new Violations([
            Violation::create($class, self::class, 'is not readonly'),
        ]);
    }
}
