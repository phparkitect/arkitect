<?php

declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Rules\Violation;
use Arkitect\Rules\ViolationMessage;
use Arkitect\Rules\Violations;

class NotResideInOneOfTheseNamespacesExactly implements Expression
{
    /** @var array<string> */
    private $namespaces;

    public function __construct(string ...$namespaces)
    {
        $this->namespaces = $namespaces;
    }

    public function describe(ClassDescription $theClass, string $because): Description
    {
        $descr = implode(', ', $this->namespaces);

        return new Description("should not reside in one of these namespaces exactly: $descr", $because);
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        $resideInNamespace = false;
        foreach ($this->namespaces as $namespace) {
            if ($theClass->namespaceMatchesExactly($namespace)) {
                $resideInNamespace = true;
            }
        }

        if ($resideInNamespace) {
            $violation = Violation::create(
                $theClass->getFQCN(),
                ViolationMessage::selfExplanatory($this->describe($theClass, $because)),
                $theClass->getFilePath()
            );
            $violations->add($violation);
        }
    }
}
