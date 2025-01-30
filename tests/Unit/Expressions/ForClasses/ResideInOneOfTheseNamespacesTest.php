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
            ['Food\*',          'Food\Vegetables\Carrot', 'matches a class in the root namespace using wildcard at ending of pattern'],
            ['Food\*',          'Food\Vegetables\Roots\Carrot', 'matches a class in a child namespace using wildcard at ending of pattern'],
            ['Food\*',          'Food\Vegetables\Roots\Orange\Carrot', 'matches a class in a child of a child namespace using wildcard at ending of pattern'],
            ['Food\*\Roots',    'Food\Vegetables\Roots\Carrot', 'matches a class in a child namespace using wildcard in the middle of pattern'],
            ['Food\*\Roots',    'Food\Vegetables\Roots\Orange\Carrot', 'matches a class in a child of a child namespace in the middle of pattern'],
            ['*\Vegetables',    'Food\Vegetables\Carrot', 'matches a class in the root namespace using wildcard at beginning of pattern'],
            ['*\Vegetables',    'Food\Vegetables\Roots\Carrot', 'matches a class in a child namespace using wildcard at beginning of pattern'],
            ['*\Vegetables',    'Food\Vegetables\Roots\Orange\Carrot', 'matches a class in a child of a child namespace using wildcard at beginning of pattern'],
        ];
    }

    /**
     * @dataProvider shouldMatchNamespacesProvider
     *
     * @param mixed $expectedNamespace
     * @param mixed $actualFQCN
     * @param mixed $explanation
     */
    public function test_it_should_match_namespace_and_descendants($expectedNamespace, $actualFQCN, $explanation): void
    {
        $haveNameMatching = new ResideInOneOfTheseNamespaces($expectedNamespace);

        $classDesc = ClassDescription::getBuilder($actualFQCN)->build();
        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations, $because);

        self::assertEquals(0, $violations->count(), $explanation);
    }

    public function test_it_should_return_false_if_not_reside_in_namespace(): void
    {
        $haveNameMatching = new ResideInOneOfTheseNamespaces('MyNamespace');

        $classDesc = ClassDescription::getBuilder('AnotherNamespace\HappyIsland')->build();
        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations, $because);

        self::assertNotEquals(0, $violations->count());
    }

    public function test_it_should_check_multiple_namespaces_in_or(): void
    {
        $haveNameMatching = new ResideInOneOfTheseNamespaces('MyNamespace', 'AnotherNamespace', 'AThirdNamespace');

        $classDesc = ClassDescription::getBuilder('AnotherNamespace\HappyIsland')->build();
        $violations = new Violations();
        $because = 'we want to add this rule for our software';
        $haveNameMatching->evaluate($classDesc, $violations, $because);
        self::assertEquals(0, $violations->count());

        $classDesc = ClassDescription::getBuilder('MyNamespace\HappyIsland')->build();
        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations, $because);
        self::assertEquals(0, $violations->count());

        $classDesc = ClassDescription::getBuilder('AThirdNamespace\HappyIsland')->build();
        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations, $because);
        self::assertEquals(0, $violations->count());

        $classDesc = ClassDescription::getBuilder('NopeNamespace\HappyIsland')->build();
        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations, $because);
        self::assertNotEquals(0, $violations->count());
    }

    public function test_duplicate_namespaces_are_removed(): void
    {
        $expression = new ResideInOneOfTheseNamespaces('A', 'B', 'A', 'C', 'D', 'D');

        self::assertSame(
            'should reside in one of these namespaces: A, B, C, D because rave',
            $expression->describe(ClassDescription::getBuilder('Marko')->build(), 'rave')->toString()
        );
    }
}
