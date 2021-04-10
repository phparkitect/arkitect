<?php
declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Expression\Expression;

class Expressions
{
    /** @var array */
    protected $expressions = [];

    public function add(Expression $expression): void
    {
        $this->expressions[] = $expression;
    }
}
