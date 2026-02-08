<?php

declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\BooleanClassExpression;

class IsTrait extends BooleanClassExpression
{
    protected function matches(ClassDescription $theClass): bool
    {
        return $theClass->isTrait();
    }

    protected function descriptionVerb(): string
    {
        return 'should be trait';
    }
}
