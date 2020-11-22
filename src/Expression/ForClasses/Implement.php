<?php
declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Expression;

class Implement implements Expression
{
    private $interface;

    public function __construct(string $interface)
    {
        $this->interface = $interface;
    }

    public function describe(ClassDescription $theClass): string
    {
        return "{$theClass->getFQCN()} implements {$this->interface}";
    }

    public function evaluate(ClassDescription $theClass): bool
    {
        return $theClass->implements($this->interface);
    }
}
