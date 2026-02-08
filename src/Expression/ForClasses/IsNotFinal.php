<?php

declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\BooleanClassExpression;

class IsNotFinal extends BooleanClassExpression
{
    public function appliesTo(ClassDescription $theClass): bool
    {
        return !($theClass->isInterface() || $theClass->isTrait() || $theClass->isEnum());
    }

    protected function matches(ClassDescription $theClass): bool
    {
        return !$theClass->isFinal();
    }

    protected function descriptionVerb(): string
    {
        return 'should not be final';
    }
}
