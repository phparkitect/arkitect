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

    public function __invoke(ClassDescription $class): bool
    {
        foreach ($this->namespaces as $namespace) {
            if ($class->isInNamespace($namespace)) {
                return true;
            }
        }

        return false;
    }
}
