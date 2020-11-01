<?php
declare(strict_types=1);

namespace Arkitect\Expression;

use Arkitect\Analyzer\ClassDescription;

class AreInvokable implements Expression
{
    public function evaluate(ClassDescription $class): bool
    {
        throw new \RuntimeException(sprintf('Unimplemented expression logic in class %s', __CLASS__));
    }

    public function toString(): string
    {
        throw new \RuntimeException(sprintf('Unimplemented toString method in class %s', __CLASS__));
    }
}
