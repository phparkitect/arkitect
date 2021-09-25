<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\ForClasses\NotResideInOneOfTheseNamespaces;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class NotResideInOneOfTheseNamespacesTest extends TestCase
{
    public function test_it_should_return_true_if_not_reside_in_namespace(): void
    {
        $haveNameMatching = new NotResideInOneOfTheseNamespaces('MyNamespace');

        $classDesc = ClassDescription::build('AnotherNamespace\HappyIsland')->get();

        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_false_if_reside_in_namespace(): void
    {
        $namespace = 'MyNamespace';
        $haveNameMatching = new NotResideInOneOfTheseNamespaces($namespace);

        $classDesc = ClassDescription::build('MyNamespace\HappyIsland')->get();

        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations);

        self::assertEquals(1, $violations->count());
        $this->assertEquals('should not reside in one of these namespaces: '.$namespace, $haveNameMatching->describe($classDesc)->toString());
    }

    public function test_it_should_check_multiple_namespaces_in_or(): void
    {
        $haveNameMatching = new NotResideInOneOfTheseNamespaces('AnotherNamespace', 'ASecondNamespace', 'AThirdNamespace');

        $classDesc = ClassDescription::build('AnotherNamespace\HappyIsland')->get();
        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations);
        self::assertEquals(1, $violations->count());

        $classDesc = ClassDescription::build('MyNamespace\HappyIsland')->get();
        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations);
        self::assertEquals(0, $violations->count());

        $classDesc = ClassDescription::build('AThirdNamespace\HappyIsland')->get();
        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations);
        self::assertEquals(1, $violations->count());
    }
}
