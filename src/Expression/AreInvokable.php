<?php
declare(strict_types=1);

namespace Arkitect\Expression;

use Arkitect\Analyzer\ClassDescription;

class AreInvokable implements Expression
{
    public function __invoke(ClassDescription $class): bool
    {
        throw new \RuntimeException(sprintf('Unimplemented expression logic in class %s', __CLASS__));
    }
}
