<?php
declare(strict_types=1);


namespace Arkitect\Constraints;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Rules\ViolationsStore;

class ConstraintsStore
{
    private $constraints = [];

    public function add($constraints): void
    {
        $this->constraints[] = $constraints;
    }

    public function checkAll(ClassDescription $classDescription, ViolationsStore $violationsStore): void
    {
        foreach ($this->constraints as $constraint) {
            if ($constraint->isViolatedBy($classDescription)) {
                $violationsStore->add($constraint->getViolationError($classDescription));
            }
        }
    }
}






