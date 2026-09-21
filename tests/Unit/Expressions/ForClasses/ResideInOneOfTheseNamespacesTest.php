<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class ResideInOneOfTheseNamespacesTest extends TestCase
{
    public static function shouldMatchNamespacesProvider(): array
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

    public static function shouldNotMatchNamespacesProvider(): array
    {
        return [
            ['Food\\Vegetables', 'Food\\VegetablesRotten\\Carrot', 'does not match a sibling namespace sharing a prefix'],
            ['Food\\Veg',        'Food\\Vegetables\\Carrot', 'does not match a namespace the pattern is only a prefix of'],
            ['Food\\*\\Roots',    'Food\\Vegetables\\RootsAndTubers\\Carrot', 'does not match a sibling namespace sharing a prefix, with a wildcard'],
        ];
    }

    /**
     * @dataProvider shouldNotMatchNamespacesProvider
     *
     * @param mixed $expectedNamespace
     * @param mixed $actualFQCN
     * @param mixed $explanation
     */
    public function test_it_should_not_match_a_namespace_that_merely_shares_a_prefix($expectedNamespace, $actualFQCN, $explanation): void
    {
        $resideInNamespace = new ResideInOneOfTheseNamespaces($expectedNamespace);

        $classDesc = ClassDescription::getBuilder($actualFQCN, 'src/Foo.php')->build();
        $violations = new Violations();
        $resideInNamespace->evaluate($classDesc, $violations, 'we want to add this rule for our software');

        self::assertEquals(1, $violations->count(), $explanation);
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

        $classDesc = ClassDescription::getBuilder($actualFQCN, 'src/Foo.php')->build();
        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations, $because);

        self::assertEquals(0, $violations->count(), $explanation);
    }

    public function test_it_should_return_false_if_not_reside_in_namespace(): void
    {
        $haveNameMatching = new ResideInOneOfTheseNamespaces('MyNamespace');

        $classDesc = ClassDescription::getBuilder('AnotherNamespace\HappyIsland', 'src/Foo.php')->build();
        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations, $because);

        self::assertNotEquals(0, $violations->count());
    }

    public function test_it_should_check_multiple_namespaces_in_or(): void
    {
        $haveNameMatching = new ResideInOneOfTheseNamespaces('MyNamespace', 'AnotherNamespace', 'AThirdNamespace');

        $classDesc = ClassDescription::getBuilder('AnotherNamespace\HappyIsland', 'src/Foo.php')->build();
        $violations = new Violations();
        $because = 'we want to add this rule for our software';
        $haveNameMatching->evaluate($classDesc, $violations, $because);
        self::assertEquals(0, $violations->count());

        $classDesc = ClassDescription::getBuilder('MyNamespace\HappyIsland', 'src/Foo.php')->build();
        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations, $because);
        self::assertEquals(0, $violations->count());

        $classDesc = ClassDescription::getBuilder('AThirdNamespace\HappyIsland', 'src/Foo.php')->build();
        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations, $because);
        self::assertEquals(0, $violations->count());

        $classDesc = ClassDescription::getBuilder('NopeNamespace\HappyIsland', 'src/Foo.php')->build();
        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations, $because);
        self::assertNotEquals(0, $violations->count());
    }
}
