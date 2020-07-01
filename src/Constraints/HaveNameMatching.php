<?php


namespace Arkitect\Constraints;

use Arkitect\Analyzer\ClassDescription;

class HaveNameMatching
{
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getViolationError(ClassDescription $classDescription): string
    {
        return "{$classDescription->getFQCN()} have name matching {$this->name}";
    }

    public function isViolatedBy(ClassDescription $theClass): bool
    {
        return !$theClass->nameMatches($this->name);
    }
}