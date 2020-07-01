<?php

namespace Arkitect\Specs;

use Arkitect\Analyzer\ClassDescription;

class DependOnClassSpec extends BaseSpec
{
    public function apply(ClassDescription $theClass): bool
    {
        return $theClass->dependsOnClass($this->getPattern());
    }
}