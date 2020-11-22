<?php
declare(strict_types=1);

namespace Arkitect\Expression;

use Arkitect\Analyzer\ClassDescription;

class DependsOnClassesInNamespace implements Expression
{
    private string $namespace;

    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;
    }

    public function describe(ClassDescription $classDescription): string
    {
        return "{$classDescription->getFQCN()} do not depends on classes in namespace {$this->namespace}";
    }

    public function evaluate(ClassDescription $theClass): bool
    {
        return !$theClass->dependsOn($this->namespace);
    }
}
