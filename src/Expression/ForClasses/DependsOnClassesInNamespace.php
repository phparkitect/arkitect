<?php
declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Expression;
use Arkitect\Expression\ExpressionDescription;

class DependsOnClassesInNamespace implements Expression
{
    private string $namespace;

    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;
    }

    public function describe(ClassDescription $theClass): ExpressionDescription
    {
        return new ExpressionDescription("{$theClass->getFQCN()} [depends|doesn't depend] on classes in namespace {$this->namespace}");
    }

    public function evaluate(ClassDescription $theClass): bool
    {
        return $theClass->dependsOn($this->namespace);
    }
}
