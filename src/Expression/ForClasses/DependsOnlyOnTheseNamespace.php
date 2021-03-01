<?php
declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Expression\PositiveDescription;

class DependsOnlyOnTheseNamespace implements Expression
{
    private array $namespaces;

    public function __construct(string ...$namespace)
    {
        $this->namespaces = $namespace;
    }

    public function describe(ClassDescription $theClass): Description
    {
        $desc = implode(', ', $this->namespaces);

        return new PositiveDescription("should [depend|not depend] only on classes in one of these namespaces: $desc");
    }

    public function evaluate(ClassDescription $theClass): bool
    {
        return $theClass->dependsOnlyOnTheseNamespaces(...$this->namespaces);
    }
}
