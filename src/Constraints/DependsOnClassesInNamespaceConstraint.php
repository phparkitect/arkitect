<?php


namespace Arkitect\Constraints;

use Arkitect\Analyzer\ClassDescription;

class DependsOnClassesInNamespaceConstraint
{
    private $namespace;

    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;
    }

    public function getViolationError(ClassDescription $classDescription): string
    {
        return "{$classDescription->getFQCN()} depends on classes in namespace {$this->namespace}";
    }

    public function isViolatedBy(ClassDescription $theClass): bool
    {
        return !$theClass->dependsOn($this->namespace);
    }
}