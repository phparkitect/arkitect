<?php


namespace Arkitect\Specs;

use Arkitect\Analyzer\ClassDescription;

class DoNotDependOnNamespaceSpec extends BaseSpec
{
    public function apply(ClassDescription $theClass): bool
    {
        return !$theClass->dependsOnNamespace($this->getPattern());
    }
}