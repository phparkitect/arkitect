<?php


namespace Arkitect\Constraints;


use Arkitect\Analyzer\ClassDescription;

class DoNotExtendClass implements Constraint
{

    public function __construct(string $class)
    {

    }

    public function getViolationError(ClassDescription $classDescription): string
    {
        // TODO: Implement getViolationError() method.
    }

    public function isViolatedBy(ClassDescription $theClass): bool
    {
        // TODO: Implement isViolatedBy() method.
    }
}