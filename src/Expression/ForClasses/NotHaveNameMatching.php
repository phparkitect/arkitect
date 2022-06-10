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

class NotHaveNameMatching implements Expression
{
    /** @var string */
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function describe(ClassDescription $theClass, string $because): Description
    {
        return new PositiveDescription("should not have a name that matches {$this->name}", $because);
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, string $because, bool $stopOnFailure): void
    {
        $fqcn = FullyQualifiedClassName::fromString($theClass->getFQCN());
        if ($fqcn->classMatches($this->name)) {
            $violation = Violation::create(
                $theClass->getFQCN(),
                $this->describe($theClass, $because)->toString()
            );
            $violations->add($violation);
        }
    }
}
