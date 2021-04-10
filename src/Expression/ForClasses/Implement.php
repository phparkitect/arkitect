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

class Implement implements Expression
{
    /** @var string */
    private $interface;

    public function __construct(string $interface)
    {
        $this->interface = $interface;
    }

    public function describe(ClassDescription $theClass): Description
    {
        return new PositiveDescription("should [implement|not implement] {$this->interface}");
    }

    public function evaluate(ClassDescription $theClass, Violations $violations): void
    {
        $interface = $this->interface;
        $interfaces = $theClass->getInterfaces();
        $implements = function (FullyQualifiedClassName $FQCN) use ($interface) {
            return $FQCN->matches($interface);
        };

        if (0 === \count(array_filter($interfaces, $implements))) {
            $violation = Violation::create(
                $theClass->getFQCN(),
                $this->describe($theClass)->toString()
            );
            $violations->add($violation);
        }
    }
}
