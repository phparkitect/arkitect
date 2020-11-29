<?php
declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Expression;

class Specs extends Expressions
{
    public function allSpecsAreMatchedBy(ClassDescription $classDescription): bool
    {
        /** @var Expression $spec */
        foreach ($this->expressions as $spec) {
            if (!$spec->evaluate($classDescription)) {
                return false;
            }
        }

        return true;
    }
}
