<?php
declare(strict_types=1);

namespace Arkitect\Expression;

use Arkitect\Analyzer\ClassDescription;

class NotHaveDependencyOutsideNamespace implements Expression
{
    private $namespace;

    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;
    }

    public function describe(ClassDescription $classDescription): string
    {
        return "{$classDescription->getFQCN()} depends on classes outside in namespace {$this->namespace}";
    }

    public function evaluate(ClassDescription $theClass): bool
    {
        return !$theClass->dependsOnly($this->namespace);
    }
}
