<?php
declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Description;
use Arkitect\Expression\Expression;
use Arkitect\Expression\PositiveDescription;

class HaveNameMatching implements Expression
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function describe(ClassDescription $theClass): Description
    {
        return new PositiveDescription("should [has|doesn't have] a name that matches {$this->name}");
    }

    public function evaluate(ClassDescription $theClass): bool
    {
        return $theClass->nameMatches($this->name);
    }
}
