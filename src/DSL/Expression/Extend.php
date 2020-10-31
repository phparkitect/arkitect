<?php
declare(strict_types=1);

namespace Arkitect\DSL\Expression;

use Arkitect\DSL\Expression;

class Extend implements Expression
{
    public function __construct(string $fqcn)
    {
    }
}
