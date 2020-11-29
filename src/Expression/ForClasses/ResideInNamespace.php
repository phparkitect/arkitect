<?php
declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Expression;
use Arkitect\Expression\ExpressionDescription;

class ResideInNamespace implements Expression
{
    private string $namespace;

    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;
    }

    public function describe(ClassDescription $theClass): ExpressionDescription
    {
        return new ExpressionDescription("{$theClass->getFQCN()} [resides|doesn't reside] in namespace {$this->namespace}");
    }

    public function evaluate(ClassDescription $theClass): bool
    {
        return $theClass->namespaceMatches($this->namespace);
    }
}
