<?php
declare(strict_types=1);


namespace Arkitect\Constraints;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Rules\Violations;

class ConstraintsStore
{
    private $constraints = [];

    public function add(Constraint $constraints): void
    {
        $this->constraints[] = $constraints;
    }

    public function checkAll(ClassDescription $classDescription, Violations $violationsStore): void
    {
        /** @var Constraint $constraint */
        foreach ($this->constraints as $constraint) {
            if ($constraint->isViolatedBy($classDescription)) {
                $violationsStore->add($constraint->getViolationError($classDescription));
            }
        }
    }
}
