<?php
declare(strict_types=1);

namespace Arkitect\Expression;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Rules\Violations;

class ExpressionsStore
{
    private $expressions = [];

    public function add(Expression $expression): void
    {
        $this->expressions[] = $expression;
    }

    public function checkAll(ClassDescription $classDescription, Violations $violations): void
    {
        /** @var Expression $expression */
        foreach ($this->expressions as $expression) {
            if (!$expression->evaluate($classDescription)) {
                $violations->add($expression->describe($classDescription));
            }
        }
    }
}
