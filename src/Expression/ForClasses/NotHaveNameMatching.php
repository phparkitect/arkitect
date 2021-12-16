<?php
declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Expression\PositiveDescription;
use Arkitect\Rules\RuleException;
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

    public function describe(ClassDescription $theClass): Description
    {
        return new PositiveDescription("should not have a name that matches {$this->name}");
    }

    public function evaluate(ClassDescription $theClass, Violations $violations, RuleException $except): void
    {
        $fqcn = FullyQualifiedClassName::fromString($theClass->getFQCN());
        if ($fqcn->classMatches($this->name) && $except->isAllowed($theClass->getFQCN())) {
            $violation = Violation::create(
                $theClass->getFQCN(),
                $this->describe($theClass)->toString()
            );
            $violations->add($violation);
        }
    }
}
