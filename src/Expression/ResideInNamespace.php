<?php
declare(strict_types=1);

namespace Arkitect\Expression;

use Arkitect\Analyzer\ClassDescription;

class ResideInNamespace implements Expression
{
    public function __construct(string ...$namespaces)
    {
    }

    public function __invoke(ClassDescription $item): bool
    {
        return true; // TODO
    }
}
