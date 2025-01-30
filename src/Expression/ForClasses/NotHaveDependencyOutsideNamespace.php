<?php

declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Rules\Violation;
use Arkitect\Rules\ViolationMessage;
use Arkitect\Rules\Violations;

class NotHaveDependencyOutsideNamespace implements Expression
{
    /** @var string */
    private $namespace;
    /** @var array */
    private $externalDependenciesToExclude;
    /** @var bool */
    private $excludeCoreNamespace;

    public function __construct(string $namespace, array $externalDependenciesToExclude = [], bool $excludeCoreNamespace = false)
    {
        $this->namespace = $namespace;
        $this->externalDependenciesToExclude = $externalDependenciesToExclude;
        $this->excludeCoreNamespace = $excludeCoreNamespace;
    }

    public function describe(ClassDescription $theClass, string $because): Description
    {
        return new Description("should not depend on classes outside namespace {$this->namespace}", $because);
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        $namespace = $this->namespace;
        $depends = function (ClassDependency $dependency) use ($namespace): bool {
            return !$dependency->getFQCN()->matches($namespace);
        };

        $dependencies = $theClass->getDependencies();
        $externalDeps = array_filter($dependencies, $depends);

        /** @var ClassDependency $externalDep */
        foreach ($externalDeps as $externalDep) {
            if ($externalDep->matchesOneOf(...$this->externalDependenciesToExclude)) {
                continue;
            }

            if ($this->excludeCoreNamespace && '' === $externalDep->getFQCN()->namespace()) {
                continue;
            }

            $violation = Violation::createWithErrorLine(
                $theClass->getFQCN(),
                ViolationMessage::withDescription(
                    $this->describe($theClass, $because),
                    "depends on {$externalDep->getFQCN()->toString()}"
                ),
                $externalDep->getLine()
            );
            $violations->add($violation);
        }
    }
}
