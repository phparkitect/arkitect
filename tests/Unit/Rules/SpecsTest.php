<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Rules;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Rules\Specs;
use PHPUnit\Framework\TestCase;

class SpecsTest extends TestCase
{
    public function test_return_false_if_not_all_specs_are_matched(): void
    {
        $specStore = new Specs();
        $specStore->add(new HaveNameMatching('Foo'));

        $classDescription = ClassDescription::build('MyNamespace\HappyIsland')->get();

        $this->assertFalse($specStore->allSpecsAreMatchedBy($classDescription));
    }

    public function test_return_true_if_all_specs_are_matched(): void
    {
        $specStore = new Specs();
        $specStore->add(new HaveNameMatching('Happy*'));

        $classDescription = ClassDescription::build('MyNamespace\HappyIsland')
            ->addDependency(new ClassDependency('Foo', 100))
            ->get();

        $this->assertTrue($specStore->allSpecsAreMatchedBy($classDescription));
    }
}
