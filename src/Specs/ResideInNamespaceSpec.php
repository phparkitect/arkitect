<?php


namespace Arkitect\Specs;

use Arkitect\Analyzer\ClassDescription;

class ResideInNamespaceSpec extends BaseSpec
{
    public function apply(ClassDescription $theClass): bool
    {
        return $theClass->isInNamespace($this->getPattern());
    }

}