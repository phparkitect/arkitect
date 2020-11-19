<?php
declare(strict_types=1);

namespace Arkitect\Expression;

use Arkitect\Analyzer\ClassDescription;

interface Expression
{
    public function describe(ClassDescription $classDescription): string;

    public function evaluate(ClassDescription $theClass): bool;
}
