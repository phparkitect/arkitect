<?php

declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\AbstractExpression;
use Arkitect\Expression\Description;
use Arkitect\Rules\Violations;

class MatchOneOfTheseNames extends AbstractExpression
{
    /** @var array<string> */
    private $names;

    public function __construct(array $names)
    {
        $this->names = $names;
    }

    public function describe(ClassDescription $theClass, string $because): Description
    {
        $names = implode(', ', $this->names);

        return new Description("should have a name that matches {$names}", $because);
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because): void
    {
        $fqcn = FullyQualifiedClassName::fromString($theClass->getFQCN());
        $matches = false;
        foreach ($this->names as $name) {
            $matches = $matches || $fqcn->classMatches($name);
        }

        if (!$matches) {
            $this->addViolation($theClass, $violations, $because);
        }
    }
}
