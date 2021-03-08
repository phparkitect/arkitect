<?php
declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Expression\PositiveDescription;
use Arkitect\Rules\Violation;
use Arkitect\Rules\Violations;

class NotHaveDependencyOutsideNamespace implements Expression
{
    private string $namespace;

    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;
    }

    public function describe(ClassDescription $theClass): Description
    {
        return new PositiveDescription("should [not depend|depend] on classes outside in namespace {$this->namespace}");
    }

    public function evaluate(ClassDescription $theClass, Violations $violations): void
    {
        $namespace = $this->namespace;
        $depends = function (ClassDependency $dependency) use ($namespace) {
            return !$dependency->getFQCN()->matches($namespace);
        };

        $dependencies = $theClass->getDependencies();
        $externalDep = \count(array_filter($dependencies, $depends));

        if (0 !== $externalDep) {
            $violation = Violation::create(
                $theClass->getFQCN(),
                $this->describe($theClass)->toString()
            );
            $violations->add($violation);
        }
    }
}
