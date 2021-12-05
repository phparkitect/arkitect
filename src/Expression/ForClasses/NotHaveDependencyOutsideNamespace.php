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
    /** @var string */
    private $namespace;

    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;
    }

    public function describe(ClassDescription $theClass): Description
    {
        return new PositiveDescription("should not depend on classes outside namespace {$this->namespace}");
    }

    public function evaluate(ClassDescription $theClass, Violations $violations): void
    {
        $namespace = $this->namespace;
        $depends = function (ClassDependency $dependency) use ($namespace): bool {
            return !$dependency->getFQCN()->matches($namespace);
        };

        $dependencies = $theClass->getDependencies();
        $externalDeps = array_filter($dependencies, $depends);

        /** @var ClassDependency $externalDep */
        foreach ($externalDeps as $externalDep) {
            $violation = Violation::createWithErrorLine(
                $theClass->getFQCN(),
                $this->describe($theClass)->toString(),
                $externalDep->getLine()
            );
            $violations->add($violation);
        }
    }
}
