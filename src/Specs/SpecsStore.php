<?php
declare(strict_types=1);

namespace Arkitect\Specs;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Expression;

class SpecsStore
{
    private $specs = [];

    public function add(Expression $spec): void
    {
        $this->specs[] = $spec;
    }

    public function allSpecsAreMatchedBy(ClassDescription $classDescription): bool
    {
        /** @var Expression $spec */
        foreach ($this->specs as $spec) {
            if (!$spec->evaluate($classDescription)) {
                return false;
            }
        }

        return true;
    }
}
