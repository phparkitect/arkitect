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

class DependsOnlyOnTheseNamespaces implements Expression
{
    /** @var array<string> */
    private array $namespaces;

    /** @var array<string> */
    private array $exclude;

    public function __construct(array $namespaces = [], array $exclude = [])
    {
        $this->namespaces = $namespaces;
        $this->exclude = $exclude;
    }

    public function describe(ClassDescription $theClass, string $because): Description
    {
        $desc = implode(', ', $this->namespaces);

        return new Description("should depend only on classes in one of these namespaces: $desc", $because);
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        $dependencies = $theClass->getDependencies();

        /** @var ClassDependency $dependency */
        foreach ($dependencies as $dependency) {
            if ($theClass->namespaceMatches($dependency->getFQCN()->namespace())) {
                continue; // skip classes in the same namespace
            }

            if ($dependency->matchesOneOf(...$this->exclude)) {
                continue; // skip excluded namespaces
            }

            if (!$dependency->matchesOneOf(...$this->namespaces)) {
                $violation = Violation::createWithErrorLine(
                    $theClass->getFQCN(),
                    ViolationMessage::withDescription(
                        $this->describe($theClass, $because),
                        "depends on {$dependency->getFQCN()->toString()}"
                    ),
                    $dependency->getLine(),
                    $theClass->getFilePath()
                );

                $violations->add($violation);
            }
        }
    }
}
