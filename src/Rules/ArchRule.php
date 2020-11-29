<?php
declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Analyzer\ClassDescription;

class ArchRule implements DSL\ArchRule
{
    private Specs $thats;

    private Constraints $shoulds;

    public function __construct(Specs $specs, Constraints $constraints)
    {
        $this->thats = $specs;
        $this->shoulds = $constraints;
    }

    public function check(ClassDescription $classDescription, Violations $violations): void
    {
        if (!$this->thats->allSpecsAreMatchedBy($classDescription)) {
            return;
        }

        $this->shoulds->checkAll($classDescription, $violations);
    }
}
