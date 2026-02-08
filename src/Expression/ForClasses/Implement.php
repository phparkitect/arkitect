<?php

declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\AbstractExpression;
use Arkitect\Expression\Description;
use Arkitect\Rules\Violations;

class Implement extends AbstractExpression
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
        $interfaces = $theClass->getInterfaces();
        $implements = static function (FullyQualifiedClassName $FQCN) use ($interface): bool {
            return $FQCN->matches($interface);
        };

        if (0 === \count(array_filter($interfaces, $implements))) {
            $this->addViolation($theClass, $violations, $because);
        }
    }
}
