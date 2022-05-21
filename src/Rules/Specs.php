<?php
declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Expression;

class Specs
{
    /** @var array */
    private $expressions = [];

    public function add(Expression $expression): void
    {
        $this->expressions[] = $expression;
    }

    public function allSpecsAreMatchedBy(ClassDescription $classDescription): bool
    {
        /** @var Expression $spec */
        foreach ($this->expressions as $spec) {
            $violations = new Violations();
            $spec->evaluate($classDescription, $violations);

            if ($violations->count() > 0) {
                return false;
            }
        }

        return true;
    }
}
