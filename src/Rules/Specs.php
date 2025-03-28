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

    public function allSpecsAreMatchedBy(ClassDescription $classDescription, string $because): bool
    {
        /** @var Expression $spec */
        foreach ($this->expressions as $spec) {
            // incremental way to introduce this method
            if (method_exists($spec, 'appliesTo')) {
                $canApply = $spec->appliesTo($classDescription);

                if (false === $canApply) {
                    return false;
                }
            }

            $violations = new Violations();
            $spec->evaluate($classDescription, $violations, $because);

            if ($violations->count() > 0) {
                return false;
            }
        }

        return true;
    }
}
