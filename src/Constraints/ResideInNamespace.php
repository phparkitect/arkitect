<?php
declare(strict_types=1);

namespace Arkitect\Constraints;

use Arkitect\Analyzer\ClassDescription;

class ResideInNamespace implements Constraint
{
    /**
     * @var string
     */
    private $namespace;

    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;
    }

    public function getViolationError(ClassDescription $classDescription): string
    {
        return "{$classDescription->getFQCN()} does not reside in namespace {$this->namespace}";
    }

    public function isViolatedBy(ClassDescription $theClass): bool
    {
        return ! $theClass->isInNamespace($this->namespace);
    }

}
