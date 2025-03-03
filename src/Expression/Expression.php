<?php

declare(strict_types=1);

namespace Arkitect\Expression;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Rules\Violations;

/**
 * A class implementing this interface represents a property a php class may or may not have.
 *
 * @example ResideInOneOfTheseNamespaces Expression tells if a class is defined in a particular namespace
 * @example HaveNameMatching tells if a class has a name matching a pattern
 */
interface Expression
{
    /**
     * Returns a human readable description of the expression
     * $theClass can be used to add contextual information.
     */
    public function describe(ClassDescription $theClass, string $because): Description;

    /**
     * Checks if the expression applies to the class passed as parameter.
     * If the current expression does not apply to the class, this method should return false.
     *
     * eg: IsAbstract does not applies to interfaces, traits, readonly classes
     *
     * Not included directly in the interface to allow incremental implementation of it in the rules.
     */
    //public function appliesTo(ClassDescription $theClass): bool;

    /**
     * Evaluates the expression for the class passed as parameter.
     * It should adds violations if rule is violated.
     */
    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void;
}
