<?php
declare(strict_types=1);

namespace Arkitect\Expression;

use Arkitect\Analyzer\ClassDescription;

class AreInvokable implements Expression
{
    public function __invoke(ClassDescription $item): bool
    {
        return true; // TODO: Implement __invoke() method.
    }
}
