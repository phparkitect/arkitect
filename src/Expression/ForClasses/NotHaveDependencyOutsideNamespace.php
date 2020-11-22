<?php
declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Expression;

class NotHaveDependencyOutsideNamespace implements Expression
{
    private $namespace;

    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;
    }

    public function describe(ClassDescription $theClass): string
    {
        return "{$theClass->getFQCN()} does not depend on classes outside in namespace {$this->namespace}";
    }

    public function evaluate(ClassDescription $theClass): bool
    {
        return $theClass->dependsOnly($this->namespace);
    }
}
