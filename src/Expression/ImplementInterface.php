<?php
declare(strict_types=1);

namespace Arkitect\Expression;

use Arkitect\Analyzer\ClassDescription;

class ImplementInterface implements Expression
{
    /**
     * @var string
     */
    private $FQCN;

    public function __construct(string $FQCN)
    {
        $this->FQCN = $FQCN;
    }

    public function evaluate(ClassDescription $class): bool
    {
        return $class->implements($this->FQCN);
    }

    public function toString(): string
    {
        return sprintf('implements %s', $this->FQCN);
    }
}
