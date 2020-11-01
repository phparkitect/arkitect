<?php
declare(strict_types=1);

namespace Arkitect\Expression;

use Arkitect\Analyzer\ClassDescription;

class ResideInNamespace implements Expression
{
    /** @var string[] */
    private $namespaces;

    public function __construct(string ...$namespaces)
    {
        $this->namespaces = $namespaces;
    }

    public function evaluate(ClassDescription $class): bool
    {
        foreach ($this->namespaces as $namespace) {
            if ($class->isInNamespace($namespace)) {
                return true;
            }
        }

        return false;
    }

    public function toString(): string
    {
        throw new \RuntimeException(sprintf('Unimplemented toString method in class %s', __CLASS__));
    }
}
