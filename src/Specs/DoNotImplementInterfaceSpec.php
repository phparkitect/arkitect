<?php

namespace Arkitect\Specs;

use Arkitect\Analyzer\ClassDescription;

class DoNotImplementInterfaceSpec extends BaseSpec
{
    public function apply(ClassDescription $theClass): bool
    {
        return !$theClass->implements($this->getPattern());
    }
}