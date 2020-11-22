<?php
declare(strict_types=1);

namespace Arkitect\Expression;

use Arkitect\Analyzer\ClassDescription;

class Implement implements Expression
{
    private string $interface;

    public function __construct(string $interface)
    {
        $this->interface = $interface;
    }

    public function describe(ClassDescription $classDescription): string
    {
        return "{$classDescription->getFQCN()} does not implement {$this->interface}";
    }

    public function evaluate(ClassDescription $theClass): bool
    {
        return !$theClass->implements($this->interface);
    }
}
