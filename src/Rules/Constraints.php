<?php
declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Expression;

class Constraints extends Expressions
{
    public function checkAll(ClassDescription $classDescription, Violations $violations): void
    {
        /** @var Expression $expression */
        foreach ($this->expressions as $expression) {
            if (!$expression->evaluate($classDescription)) {
                $violation = Violation::create(
                    $classDescription->getFQCN(),
                    $expression->describe($classDescription)->toString()
                );
                $violations->add($violation);
            }
        }
    }
}
