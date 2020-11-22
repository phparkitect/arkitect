<?php
declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Expression;

class DependsOnClassesInNamespace implements Expression
{
    /**
     * @var string
     */
    private $namespace;

    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;
    }

    public function describe(ClassDescription $theClass): string
    {
        return "{$theClass->getFQCN()} depends on classes in namespace {$this->namespace}";
    }

    public function evaluate(ClassDescription $theClass): bool
    {
        return $theClass->dependsOn($this->namespace);
    }
}
