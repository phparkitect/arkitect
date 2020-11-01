<?php
declare(strict_types=1);

namespace Arkitect\Expression;

use Arkitect\Analyzer\ClassDescription;

interface Expression
{
    public function __invoke(ClassDescription $class): bool;
}
