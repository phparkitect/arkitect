<?php

declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\BooleanClassExpression;

class IsNotEnum extends BooleanClassExpression
{
    protected function matches(ClassDescription $theClass): bool
    {
        return !$theClass->isEnum();
    }

    protected function descriptionVerb(): string
    {
        return 'should not be an enum';
    }
}
