<?php

namespace Arkitect\Specs;

use Arkitect\Analyzer\ClassDescription;

class DoNotHaveNameMatchingSpec extends BaseSpec
{
    public function apply(ClassDescription $theClass): bool
    {
        return !$theClass->nameMatches($this->getPattern());
    }

}