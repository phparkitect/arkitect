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
            // incremental way to introduce this method
            if (method_exists($expression, 'appliesTo') && !$expression->appliesTo($classDescription)) {
                continue;
            }

            $expression->evaluate($classDescription, $violations, $because);
        }
    }
}
