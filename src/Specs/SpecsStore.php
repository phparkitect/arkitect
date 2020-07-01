<?php

namespace Arkitect\Specs;

use Arkitect\Analyzer\ClassDescription;

class SpecsStore
{
    private $specs = [];

    public function add($spec): void
    {
        $this->specs[] = $spec;
    }

    public function allSpecsAreMatchedBy(ClassDescription $classDescription): bool
    {
        foreach ($this->specs as $spec) {

            if (!$spec->apply($classDescription)) {
                return false;
            }
        }

        return true;
    }
}