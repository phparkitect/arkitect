<?php
declare(strict_types=1);

namespace Arkitect\Expression;

use Arkitect\Analyzer\ClassDescription;

interface Expression
{
    public function evaluate(ClassDescription $class): bool;

    public function toString(): string;
}
