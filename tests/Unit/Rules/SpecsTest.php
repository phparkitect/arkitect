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

        $classDescription = ClassDescription::getBuilder('MyNamespace\HappyIsland', 'src/Foo.php')->build();
        $because = 'we want to add this rule for our software';

        self::assertFalse($specStore->allSpecsAreMatchedBy($classDescription, $because));
    }

    public function test_return_true_if_all_specs_are_matched(): void
    {
        $specStore = new Specs();
        $specStore->add(new HaveNameMatching('Happy*'));

        $classDescription = ClassDescription::getBuilder('MyNamespace\HappyIsland', 'src/Foo.php')
            ->addDependency(new ClassDependency('Foo', 100))
            ->build();
        $because = 'we want to add this rule for our software';

        self::assertTrue($specStore->allSpecsAreMatchedBy($classDescription, $because));
    }
}
