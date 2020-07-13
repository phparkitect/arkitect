<?php
declare(strict_types=1);


namespace Arkitect\Constraints;

use Arkitect\Analyzer\ClassDescription;

class DependsOnClassesInNamespaceConstraint implements Constraint
{
    private $namespace;

    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;
    }

    public function getViolationError(ClassDescription $classDescription): string
    {
        return "{$classDescription->getFQCN()} do not depends on classes in namespace {$this->namespace}";
    }

    public function isViolatedBy(ClassDescription $theClass): bool
    {
        return !$theClass->dependsOn($this->namespace);
    }
}
