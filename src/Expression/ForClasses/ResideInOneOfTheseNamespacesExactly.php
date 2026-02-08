<?php

declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\AbstractExpression;
use Arkitect\Expression\Description;
use Arkitect\Rules\Violations;

class ResideInOneOfTheseNamespacesExactly extends AbstractExpression
{
    /** @var array<string> */
    private $namespaces;

    public function __construct(string ...$namespaces)
    {
        $this->namespaces = array_values(array_unique($namespaces));
    }

    public function describe(ClassDescription $theClass, string $because): Description
    {
        $descr = implode(', ', $this->namespaces);

        return new Description("should reside in one of these namespaces exactly: $descr", $because);
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        $resideInNamespace = false;
        foreach ($this->namespaces as $namespace) {
            if ($theClass->namespaceMatchesExactly($namespace)) {
                $resideInNamespace = true;
            }
        }

        if (!$resideInNamespace) {
            $this->addViolation($theClass, $violations, $because);
        }
    }
}
