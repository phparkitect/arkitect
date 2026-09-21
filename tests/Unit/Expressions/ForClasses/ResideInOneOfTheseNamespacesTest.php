<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Exceptions\InvalidPatternException;
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

    /**
     * @dataProvider provideNamespaces
     */
    public function test_it_should_match_the_namespace_a_class_resides_in(string $fqcn, string $namespace, bool $shouldMatch): void
    {
        $resideInOneOfTheseNamespaces = new ResideInOneOfTheseNamespaces($namespace);

        $classDesc = ClassDescription::getBuilder($fqcn, 'src/Foo.php')->build();
        $violations = new Violations();
        $resideInOneOfTheseNamespaces->evaluate($classDesc, $violations, 'we want to add this rule for our software');

        self::assertEquals($shouldMatch ? 0 : 1, $violations->count());
    }

    public function test_an_empty_namespace_is_rejected_rather_than_silently_selecting(): void
    {
        $classDesc = ClassDescription::getBuilder('App\Foo\Domain\Bar', 'src/Foo.php')->build();

        $this->expectException(InvalidPatternException::class);

        (new ResideInOneOfTheseNamespaces(''))->evaluate($classDesc, new Violations(), 'because');
    }

    public static function provideNamespaces(): array
    {
        return [
            'a wildcard in the middle reaches a sub namespace' => ['App\Foo\Infrastructure\Bar', 'App\*\Infrastructure', true],
            'a wildcard in the middle reaches a nested sub namespace' => ['App\Foo\Infrastructure\Doctrine\Bar', 'App\*\Infrastructure', true],
            'a trailing wildcard keeps working' => ['App\Foo\Infrastructure\Bar', 'App\*\Infrastructure\*', true],
            'a namespace without wildcards reaches a sub namespace' => ['App\Infrastructure\Doctrine\Bar', 'App\Infrastructure', true],
            'a namespace never reaches into a longer name' => ['App\FooBar\Baz', 'App\Foo', false],
            'a wildcard never reaches into a longer name' => ['App\Foo\InfrastructureLegacy\Bar', 'App\*\Infrastructure', false],
            'a different namespace does not match' => ['App\Foo\Domain\Bar', 'App\*\Infrastructure', false],
        ];
    }
}
