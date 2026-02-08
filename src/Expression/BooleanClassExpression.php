<?php

declare(strict_types=1);

namespace Arkitect\Expression;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Rules\Violations;

/**
 * Base class for expressions that check a single boolean property of a class.
 *
 * Subclasses only need to define what to check and what to say about it.
 */
abstract class BooleanClassExpression extends AbstractExpression
{
    abstract protected function matches(ClassDescription $theClass): bool;

    abstract protected function descriptionVerb(): string;

    public function describe(ClassDescription $theClass, string $because): Description
    {
        return new Description("{$theClass->getName()} {$this->descriptionVerb()}", $because);
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        if ($this->matches($theClass)) {
            return;
        }

        $this->addViolation($theClass, $violations, $because);
    }
}
