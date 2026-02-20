<?php

declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Rules\Violation;
use Arkitect\Rules\ViolationMessage;
use Arkitect\Rules\Violations;

class Implement implements Expression
{
    /** @var string */
    private $interface;

    public function __construct(string $interface)
    {
        $this->interface = $interface;
    }

    public function describe(ClassDescription $theClass, string $because): Description
    {
        return new Description("should implement {$this->interface}", $because);
    }

    public function appliesTo(ClassDescription $theClass): bool
    {
        return !($theClass->isInterface() || $theClass->isTrait());
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        if ($theClass->isInterface() || $theClass->isTrait()) {
            return;
        }

        $interface = $this->interface;

        $reflection = new \ReflectionClass($theClass->getFQCN());
        $allInterfaces = $reflection->getInterfaceNames();
        $found = \count(array_filter(
            $allInterfaces,
            static fn (string $name): bool => FullyQualifiedClassName::fromString($name)->matches($interface)
        )) > 0;

        if (!$found) {
            $violations->add(Violation::create(
                $theClass->getFQCN(),
                ViolationMessage::selfExplanatory($this->describe($theClass, $because)),
                $theClass->getFilePath()
            ));
        }
    }
}
