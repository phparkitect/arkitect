<?php

declare(strict_types=1);

namespace Arkitect\Expression;

interface MergeableExpression
{
    /**
     * @throws \InvalidArgumentException when the given expression is not of the same type
     */
    public function mergeWith(Expression $expression): Expression;
}
