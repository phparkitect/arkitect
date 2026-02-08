<?php

declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\BooleanClassExpression;

class IsNotTrait extends BooleanClassExpression
{
    protected function matches(ClassDescription $theClass): bool
    {
        return !$theClass->isTrait();
    }

    protected function descriptionVerb(): string
    {
        return 'should not be trait';
    }
}
