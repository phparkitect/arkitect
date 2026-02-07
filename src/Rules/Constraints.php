<?php

declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Expression;

class Constraints
{
    /** @var array */
    private $expressions = [];

    public function add(Expression $expression): void
    {
        $this->expressions[] = $expression;
    }

    public function checkAll(ClassDescription $classDescription, Violations $violations, string $because): void
    {
        /** @var Expression $expression */
        foreach ($this->expressions as $expression) {
            $expression->evaluate($classDescription, $violations, $because);
        }
    }

    public function describe(): string
    {
        $descriptions = [];

        foreach ($this->expressions as $expression) {
            $descriptions[] = self::expressionToReadableString($expression);
        }

        return implode(' and ', $descriptions);
    }

    private static function expressionToReadableString(Expression $expression): string
    {
        $shortName = (new \ReflectionClass($expression))->getShortName();

        return lcfirst(implode(' ', preg_split('/(?=[A-Z])/', $shortName, -1, \PREG_SPLIT_NO_EMPTY)));
    }
}
