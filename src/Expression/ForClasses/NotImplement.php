<?php
declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Expression\PositiveDescription;
use Arkitect\Rules\Violation;
use Arkitect\Rules\Violations;

class NotImplement implements Expression
{
    /** @var string */
    private $interface;

    public function __construct(string $interface)
    {
        $this->interface = $interface;
    }

    public function describe(ClassDescription $theClass, string $because): Description
    {
        return new PositiveDescription("should not implement {$this->interface}", $because);
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        $interface = $this->interface;
        $interfaces = $theClass->getInterfaces();
        $implements = function (FullyQualifiedClassName $FQCN) use ($interface): bool {
            return $FQCN->matches($interface);
        };

        if (\count(array_filter($interfaces, $implements)) > 0) {
            $violation = Violation::create(
                $theClass->getFQCN(),
                $this->describe($theClass, $because)->toString()
            );
            $violations->add($violation);
        }
    }
}
