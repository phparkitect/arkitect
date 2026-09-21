<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\ForClasses\NotResideInTheseNamespaces;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class NotResideInTheseNamespacesTest extends TestCase
{
    public function test_it_should_return_true_if_not_reside_in_namespace(): void
    {
        $haveNameMatching = new NotResideInTheseNamespaces('MyNamespace');

        $classDesc = ClassDescription::getBuilder('AnotherNamespace\HappyIsland', 'src/Foo.php')->build();
        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_false_if_reside_in_namespace(): void
    {
        $namespace = 'MyNamespace';
        $haveNameMatching = new NotResideInTheseNamespaces($namespace);

        $classDesc = ClassDescription::getBuilder('MyNamespace\HappyIsland', 'src/Foo.php')->build();
        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations, $because);

        self::assertEquals(1, $violations->count());
        self::assertEquals(
            'should not reside in one of these namespaces: '.$namespace.' because we want to add this rule for our software',
            $haveNameMatching->describe($classDesc, $because)->toString()
        );
    }

    public function test_it_should_check_multiple_namespaces_in_or(): void
    {
        $haveNameMatching = new NotResideInTheseNamespaces('AnotherNamespace', 'ASecondNamespace', 'AThirdNamespace');

        $classDesc = ClassDescription::getBuilder('AnotherNamespace\HappyIsland', 'src/Foo.php')->build();
        $violations = new Violations();
        $because = 'we want to add this rule for our software';
        $haveNameMatching->evaluate($classDesc, $violations, $because);
        self::assertEquals(1, $violations->count());

        $classDesc = ClassDescription::getBuilder('MyNamespace\HappyIsland', 'src/Foo.php')->build();
        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations, $because);
        self::assertEquals(0, $violations->count());

        $classDesc = ClassDescription::getBuilder('AThirdNamespace\HappyIsland', 'src/Foo.php')->build();
        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations, $because);
        self::assertEquals(1, $violations->count());
    }

    /**
     * @dataProvider provideWildcardNamespaces
     */
    public function test_it_should_match_sub_namespaces_of_a_wildcard_namespace(
        string $fqcn,
        string $namespace,
        bool $shouldMatch,
    ): void {
        $notResideInTheseNamespaces = new NotResideInTheseNamespaces($namespace);

        $classDesc = ClassDescription::getBuilder($fqcn, 'src/Foo.php')->build();
        $violations = new Violations();
        $notResideInTheseNamespaces->evaluate($classDesc, $violations, 'we want to add this rule for our software');

        self::assertEquals($shouldMatch ? 1 : 0, $violations->count());
    }

    public static function provideWildcardNamespaces(): array
    {
        return [
            'wildcard in the middle matches a sub namespace' => ['App\Foo\Infrastructure\Bar', 'App\*\Infrastructure', true],
            'wildcard in the middle matches the namespace itself' => ['App\Foo\Infrastructure', 'App\*\Infrastructure', true],
            'wildcard in the middle matches a nested sub namespace' => ['App\Foo\Infrastructure\Doctrine\Bar', 'App\*\Infrastructure', true],
            'trailing wildcard keeps matching a sub namespace' => ['App\Foo\Infrastructure\Bar', 'App\*\Infrastructure\*', true],
            'a longer namespace name is not a sub namespace' => ['App\Foo\InfrastructureLegacy\Bar', 'App\*\Infrastructure', false],
            'a different namespace does not match' => ['App\Foo\Domain\Bar', 'App\*\Infrastructure', false],
            'leading wildcard matches a sub namespace' => ['App\Foo\Infrastructure\Bar', '*\Infrastructure', true],
            'namespace without wildcard still matches a sub namespace' => ['App\Infrastructure\Doctrine\Bar', 'App\Infrastructure', true],
            'namespace with a trailing separator still matches a sub namespace' => ['App\Infrastructure\Doctrine\Bar', 'App\Infrastructure\\', true],
        ];
    }
}
