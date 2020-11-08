<?php
declare(strict_types=1);

namespace Arkitect\Constraints;

use Arkitect\Analyzer\ClassDescription;

class NotHaveDependencyOutsideNamespace implements Constraint
{
    private $namespace;

    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;
    }

    public function getViolationError(ClassDescription $classDescription): string
    {
        return "{$classDescription->getFQCN()} depends on classes outside in namespace {$this->namespace}";
    }

    public function isViolatedBy(ClassDescription $theClass): bool
    {
        return !$theClass->dependsOnly($this->namespace);
    }
}
