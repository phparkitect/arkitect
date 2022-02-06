<?php
declare(strict_types=1);

namespace Arkitect\Rules\DSL;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\ClassDescriptionCollection;
use Arkitect\Rules\Violations;

interface ArchRule
{
    public function check(ClassDescription $classDescription, Violations $violations, ClassDescriptionCollection $collection): void;
}
