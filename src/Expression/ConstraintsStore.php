<?php
declare(strict_types=1);

namespace Arkitect\Expression;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Rules\Violations;

class ConstraintsStore
{
    private $expressions = [];

    public function add(Expression $expressions): void
    {
        $this->constraints[] = $expressions;
    }

    public function checkAll(ClassDescription $classDescription, Violations $violationsStore): void
    {
        /** @var Expression $expression */
        foreach ($this->constraints as $expression) {
            if ($expression->isViolatedBy($classDescription)) {
                $violationsStore->add($expression->getViolationError($classDescription));
            }
        }
    }
}
