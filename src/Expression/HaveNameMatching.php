<?php
declare(strict_types=1);

namespace Arkitect\Expression;

use Arkitect\Analyzer\ClassDescription;

class HaveNameMatching implements Expression
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function describe(ClassDescription $classDescription): string
    {
        return "{$classDescription->getFQCN()} has a name that doesn't match {$this->name}";
    }

    public function evaluate(ClassDescription $theClass): bool
    {
        return !$theClass->nameMatches($this->name);
    }
}
