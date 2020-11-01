<?php
declare(strict_types=1);

namespace Arkitect\Expression;

use Arkitect\Analyzer\ClassDescription;

class HaveNameStartingWith implements Expression
{
    public function __construct(string $string)
    {
    }

    public function __invoke(ClassDescription $class): bool
    {
        return true; // TODO
    }
}
