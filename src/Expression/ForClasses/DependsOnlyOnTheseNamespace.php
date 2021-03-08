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

    public function evaluate(ClassDescription $theClass, Violations $violations): void
    {
        $dependencies = $theClass->getDependencies();
        /** @var ClassDependency $dependency */
        foreach ($dependencies as $dependency) {
            if (!$dependency->matchesOneOf(...$this->namespaces)) {
                $violation = Violation::create(
                    $theClass->getFQCN(),
                    $this->describe($theClass)->toString()
                );
                $violations->add($violation);
            }
        }
    }
}
