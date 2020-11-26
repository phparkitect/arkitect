<?php
declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Expression\Expression;

class Expressions
{
    protected array $expressions = [];

    public function add(Expression $expression): void
    {
        $this->expressions[] = $expression;
    }
}
