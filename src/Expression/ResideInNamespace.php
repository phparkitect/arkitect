<?php
declare(strict_types=1);

namespace Arkitect\Expression;

use Arkitect\Analyzer\ClassDescription;

class ResideInNamespace implements Expression
{
    /**
     * @var string
     */
    private $namespace;

    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;
    }

    public function describe(ClassDescription $classDescription): string
    {
        return "{$classDescription->getFQCN()} does not reside in namespace {$this->namespace}";
    }

    public function evaluate(ClassDescription $theClass): bool
    {
        return !$theClass->isInNamespace($this->namespace);
    }
}
