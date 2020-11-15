<?php
declare(strict_types=1);

namespace Arkitect\Expression;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Rules\Violations;

class ExpressionsStore
{
    private $expressions = [];

    public function add(Expression $expressions): void
    {
        $this->expressions[] = $expressions;
    }

    public function checkAll(ClassDescription $classDescription, Violations $violationsStore): void
    {
        /** @var Expression $expression */
        foreach ($this->expressions as $expression) {
            if ($expression->isViolatedBy($classDescription)) {
                $violationsStore->add($expression->getViolationError($classDescription));
            }
        }
    }
}
