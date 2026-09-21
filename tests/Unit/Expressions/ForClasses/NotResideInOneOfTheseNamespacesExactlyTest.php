<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\ForClasses\NotResideInOneOfTheseNamespacesExactly;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class NotResideInOneOfTheseNamespacesExactlyTest extends TestCase
{
    public function test_it_should_return_true_if_not_reside_in_namespace(): void
    {
        $haveNameMatching = new NotResideInOneOfTheseNamespacesExactly('MyNamespace');

        $classDesc = ClassDescription::getBuilder('AnotherNamespace\HappyIsland', 'src/Foo.php')->build();
        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_true_if_reside_in_child_namespace(): void
    {
        $haveNameMatching = new NotResideInOneOfTheseNamespacesExactly('MyNamespace');

        $classDesc = ClassDescription::getBuilder('MyNamespace\Child\HappyIsland', 'src/Foo.php')->build();
        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations, $because);

        self::assertEquals(0, $violations->count(), 'should not violate when in child namespace');
    }

    public function test_it_should_return_false_if_reside_in_exact_namespace(): void
    {
        $namespace = 'MyNamespace';
        $haveNameMatching = new NotResideInOneOfTheseNamespacesExactly($namespace);

        $classDesc = ClassDescription::getBuilder('MyNamespace\HappyIsland', 'src/Foo.php')->build();
        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations, $because);

        self::assertEquals(1, $violations->count());
        self::assertEquals(
            'should not reside in one of these namespaces exactly: '.$namespace.' because we want to add this rule for our software',
            $haveNameMatching->describe($classDesc, $because)->toString()
        );
    }

    public function test_it_should_check_multiple_namespaces_in_or(): void
    {
        $haveNameMatching = new NotResideInOneOfTheseNamespacesExactly('AnotherNamespace', 'ASecondNamespace', 'AThirdNamespace');

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
     * @dataProvider provideNamespacesAgainstCarrot
     */
    public function test_it_matches_only_the_namespace_the_class_sits_in(string $namespace, bool $shouldMatch): void
    {
        $notResideInOneOfTheseNamespacesExactly = new NotResideInOneOfTheseNamespacesExactly($namespace);

        $classDesc = ClassDescription::getBuilder('Food\Vegetables\Roots\Carrot', 'src/Carrot.php')->build();
        $violations = new Violations();
        $notResideInOneOfTheseNamespacesExactly->evaluate($classDesc, $violations, 'we want to add this rule for our software');

        self::assertEquals($shouldMatch ? 1 : 0, $violations->count());
    }

    /**
     * The "Exactly" rules differ from their recursive siblings on one axis only:
     * how deep they reach. A class matches when the namespace it sits in is one
     * the pattern denotes, and a namespace below that one is somebody else's.
     *
     * The pattern axis is unaffected: wildcards work here as everywhere else,
     * they simply pick which namespace, not how far down the rule reaches.
     *
     * The class under test is Food\Vegetables\Roots\Carrot, so the namespace
     * it sits in is Food\Vegetables\Roots.
     *
     * @return array<string, array{string, bool}>
     */
    public static function provideNamespacesAgainstCarrot(): array
    {
        return [
            // the namespace the class sits in, reached in four ways
            'the namespace itself' => ['Food\Vegetables\Roots', true],
            'a wildcard in the middle of it' => ['Food\*\Roots', true],
            'a wildcard at the end of it' => ['Food\*', true],
            'a wildcard at the beginning of it' => ['*\Roots', true],
            'a single-character wildcard' => ['Food\Vegetable?\Roots', true],

            // anywhere else is somebody else's namespace, wildcard or not
            'a parent namespace' => ['Food\Vegetables', false],
            'a parent namespace through a wildcard' => ['*\Vegetables', false],
            'the root namespace' => ['Food', false],
            'a child namespace' => ['Food\Vegetables\Roots\Orange', false],
            'a child namespace through a wildcard' => ['Food\*\Roots\Orange', false],
            'a sibling namespace' => ['Food\Vegetables\Leaves', false],
            'a sibling namespace through a wildcard' => ['Food\*\Leaves', false],
            'a longer name than the one it sits in' => ['Food\Vegetables\RootsAndTubers', false],
            'a longer name reached through a wildcard' => ['Food\*\RootsAndTubers', false],
            'the global namespace' => ['', false],
        ];
    }
}
