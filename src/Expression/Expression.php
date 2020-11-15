<?php
declare(strict_types=1);

namespace Arkitect\Expression;

use Arkitect\Analyzer\ClassDescription;

interface Expression
{
    public function getViolationError(ClassDescription $classDescription): string;

    public function isViolatedBy(ClassDescription $theClass): bool;
}
