<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\ClassDescriptionCollection;
use Arkitect\Expression\ForClasses\NotResideInTheseNamespaces;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class NotResideInTheseNamespacesTest extends TestCase
{
    public function test_it_should_return_true_if_not_reside_in_namespace(): void
    {
        $haveNameMatching = new NotResideInTheseNamespaces('MyNamespace');

        $classDesc = ClassDescription::build('AnotherNamespace\HappyIsland')->get();

        $classDescriptionCollection = new ClassDescriptionCollection();
        $classDescriptionCollection->add($classDesc);

        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations, $classDescriptionCollection);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_false_if_reside_in_namespace(): void
    {
        $namespace = 'MyNamespace';
        $haveNameMatching = new NotResideInTheseNamespaces($namespace);

        $classDesc = ClassDescription::build('MyNamespace\HappyIsland')->get();

        $classDescriptionCollection = new ClassDescriptionCollection();
        $classDescriptionCollection->add($classDesc);

        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations, $classDescriptionCollection);

        self::assertEquals(1, $violations->count());
        $this->assertEquals('should not reside in one of these namespaces: '.$namespace, $haveNameMatching->describe($classDesc)->toString());
    }

    public function test_it_should_check_multiple_namespaces_in_or(): void
    {
        $haveNameMatching = new NotResideInTheseNamespaces('AnotherNamespace', 'ASecondNamespace', 'AThirdNamespace');

        $classDesc = ClassDescription::build('AnotherNamespace\HappyIsland')->get();

        $classDescriptionCollection = new ClassDescriptionCollection();
        $classDescriptionCollection->add($classDesc);

        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations, $classDescriptionCollection);
        self::assertEquals(1, $violations->count());

        $classDesc = ClassDescription::build('MyNamespace\HappyIsland')->get();

        $classDescriptionCollection = new ClassDescriptionCollection();
        $classDescriptionCollection->add($classDesc);

        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations, $classDescriptionCollection);
        self::assertEquals(0, $violations->count());

        $classDesc = ClassDescription::build('AThirdNamespace\HappyIsland')->get();
        $classDescriptionCollection = new ClassDescriptionCollection();
        $classDescriptionCollection->add($classDesc);

        $violations = new Violations();
        $haveNameMatching->evaluate($classDesc, $violations, $classDescriptionCollection);
        self::assertEquals(1, $violations->count());
    }
}
