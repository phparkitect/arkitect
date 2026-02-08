<?php

declare(strict_types=1);

namespace Arkitect\Expression\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\BooleanClassExpression;

class IsNotReadonly extends BooleanClassExpression
{
    public function appliesTo(ClassDescription $theClass): bool
    {
        return !($theClass->isInterface() || $theClass->isTrait() || $theClass->isEnum());
    }

    protected function matches(ClassDescription $theClass): bool
    {
        return !$theClass->isReadonly();
    }

    protected function descriptionVerb(): string
    {
        return 'should not be readonly';
    }
}
