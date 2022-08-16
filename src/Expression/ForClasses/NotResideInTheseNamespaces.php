<?php

declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Rules\Violation;
use Arkitect\Rules\ViolationMessage;
use Arkitect\Rules\Violations;

class NotResideInTheseNamespaces implements Expression
{
    /** @var string[] */
    private $namespaces;

    public function __construct(string ...$namespaces)
    {
        $this->namespaces = $namespaces;
    }

    public function describe(ClassDescription $theClass, string $because): Description
    {
        $descr = implode(', ', $this->namespaces);

        return new Description("should not reside in one of these namespaces: $descr", $because);
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        $resideInNamespace = false;
        foreach ($this->namespaces as $namespace) {
            if ($theClass->namespaceMatches($namespace)) {
                $resideInNamespace = true;
            }
        }

        if ($resideInNamespace) {
            $violation = Violation::create(
                $theClass->getFQCN(),
                ViolationMessage::selfExplanatory($this->describe($theClass, $because))
            );
            $violations->add($violation);
        }
    }
}
