<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class ResideInOneOfTheseNamespacesTest extends TestCase
{
    public function shouldMatchNamespacesProvider(): array
    {
        return [
            ['Food\Vegetables', 'Food\Vegetables\Carrot', 'matches a class in the root namespace'],
            ['Food\Vegetables', 'Food\Vegetables\Roots\Carrot', 'matches a class in a child namespace'],
            ['Food\Vegetables', 'Food\Vegetables\Roots\Orange\Carrot', 'matches a class in a child of a child namespace'],
            //
            ['*\Vegetables', 'Food\Vegetables\Carrot', 'matches a class in the root namespace'],
            //
            ['Food\*', 'Food\Vegetables\Carrot', 'matches a class in the root namespace'],
        ];
    }

    /**
     * @dataProvider shouldMatchNamespacesProvider
     *
     * @param mixed $expectedNamespace
     * @param mixed $actualFQCN
     */
    public function test_it_should_match_namespace_and_descendants($expectedNamespace, $actualFQCN): void
    {
        $haveNameMatching = new ResideInOneOfTheseNamespaces($expectedNamespace);

        $classDesc = ClassDescription::build($actualFQCN)->get();

        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_false_if_not_reside_in_namespace(): void
    {
        $haveNameMatching = new ResideInOneOfTheseNamespaces('MyNamespace');

        $classDesc = ClassDescription::build('AnotherNamespace\HappyIsland')->get();

        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations);

        self::assertNotEquals(0, $violations->count());
    }

    public function test_it_should_check_multiple_namespaces_in_or(): void
    {
        $haveNameMatching = new ResideInOneOfTheseNamespaces('MyNamespace', 'AnotherNamespace', 'AThirdNamespace');

        $classDesc = ClassDescription::build('AnotherNamespace\HappyIsland')->get();
        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations);
        self::assertEquals(0, $violations->count());

        $classDesc = ClassDescription::build('MyNamespace\HappyIsland')->get();
        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations);
        self::assertEquals(0, $violations->count());

        $classDesc = ClassDescription::build('AThirdNamespace\HappyIsland')->get();
        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations);
        self::assertEquals(0, $violations->count());

        $classDesc = ClassDescription::build('NopeNamespace\HappyIsland')->get();
        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations);
        self::assertNotEquals(0, $violations->count());
    }
}
