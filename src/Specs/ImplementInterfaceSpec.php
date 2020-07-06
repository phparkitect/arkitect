<?php
declare(strict_types=1);

namespace Arkitect\Specs;

use Arkitect\Analyzer\ClassDescription;

class ImplementInterfaceSpec extends BaseSpec
{
    public function apply(ClassDescription $theClass): bool
    {
        return $theClass->implements($this->getPattern());
    }
}
