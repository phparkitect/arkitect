<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Rules\DSL\ArchRule;
use Arkitect\Rules\Violation;
use Arkitect\Rules\Violations;

class FakeRule implements ArchRule
{
    public function check(ClassDescription $classDescription, Violations $violations): void
    {
        $violations->add(Violation::create('fqcn', 'error'));
    }
}
