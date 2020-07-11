<?php

namespace Arkitect\Constraints;

use Arkitect\Analyzer\ClassDescription;

interface Constraint
{
    public function getViolationError(ClassDescription $classDescription): string;

    public function isViolatedBy(ClassDescription $theClass): bool;
}