<?php
declare(strict_types=1);

namespace Arkitect\Expression;

/**
 * A class implementing ExpressionDescription should provide a string representation of an Expression
 * Since this description could be parametric and needing processing, a getPattern method is provided to
 * return the raw version of the string.
 */
interface Description
{
    /**
     * Returns the human readable description for an expression.
     */
    public function toString(): string;

    /**
     * Returns the raw string.
     */
    public function getPattern(): string;
}
