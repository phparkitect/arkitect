<?php
declare(strict_types=1);


namespace Arkitect\Constraints;

use Arkitect\Analyzer\ClassDescription;

class HaveNameMatching implements Constraint
{
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getViolationError(ClassDescription $classDescription): string
    {
        return "{$classDescription->getFQCN()} has a name that doesn't match {$this->name}";
    }

    public function isViolatedBy(ClassDescription $theClass): bool
    {
        return !$theClass->nameMatches($this->name);
    }
}
