<?php
declare(strict_types=1);

namespace Arkitect\Specs;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Constraints\Constraint;

class SpecsStore
{
    private $specs = [];

    public function add(Constraint $spec): void
    {
        $this->specs[] = $spec;
    }

    public function allSpecsAreMatchedBy(ClassDescription $classDescription): bool
    {
        /** @var Constraint $spec */
        foreach ($this->specs as $spec) {
            if ($spec->isViolatedBy($classDescription)) {
                return false;
            }
        }

        return true;
    }
}
