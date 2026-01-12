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
}
