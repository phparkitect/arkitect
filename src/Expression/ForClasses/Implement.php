<?php
declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\Expression;
use Arkitect\Expression\ExpressionDescription;

class Implement implements Expression
{
    private string $interface;

    public function __construct(string $interface)
    {
        $this->interface = $interface;
    }

    public function describe(ClassDescription $theClass): ExpressionDescription
    {
        return new ExpressionDescription("{$theClass->getFQCN()} [implements|does not implement] {$this->interface}");
    }

    public function evaluate(ClassDescription $theClass): bool
    {
        return $theClass->implements($this->interface);
    }
}
