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

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        $interface = $this->interface;
        $interfaces = $theClass->getInterfaces();
        $implements = function (FullyQualifiedClassName $FQCN) use ($interface): bool {
            return $FQCN->matches($interface);
        };

        if (0 === \count(array_filter($interfaces, $implements))) {
            $violation = Violation::create(
                $theClass->getFQCN(),
                ViolationMessage::selfExplanatory($this->describe($theClass, $because))
            );
            $violations->add($violation);
        }
    }
}
