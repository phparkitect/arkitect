<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class NotDependsOnTheseNamespacesTest extends TestCase
{
    public function test_it_should_return_true_if_it_has_no_dependencies(): void
    {
        $notDependOnClasses = new NotDependsOnTheseNamespaces('myNamespace');

        $classDescription = ClassDescription::build('HappyIsland\Myclass')->build();
        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $notDependOnClasses->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_true_if_not_depends_on_namespace(): void
    {
        $notDependOnClasses = new NotDependsOnTheseNamespaces('myNamespace');

        $classDescription = ClassDescription::build('HappyIsland\Myclass')
            ->addDependency(new ClassDependency('myNamespace\Banana', 0))
            ->addDependency(new ClassDependency('anotherNamespace\Banana', 1))
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $notDependOnClasses->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
        $this->assertEquals(
            'depends on myNamespace\Banana, but should not depend on these namespaces: myNamespace because we want to add this rule for our software',
            $violations->get(0)->getError()
        );
    }

    public function test_it_should_return_true_if_depends_on_class_in_root_namespace(): void
    {
        $notDependOnClasses = new NotDependsOnTheseNamespaces('myNamespace');

        $classDescription = ClassDescription::build('HappyIsland\Myclass')
            ->addDependency(new ClassDependency('myNamespace\Banana', 0))
            ->addDependency(new ClassDependency('\anotherNamespace\Banana', 1))
            ->addDependency(new ClassDependency('\DateTime', 10))
            ->build();

        $violations = new Violations();
        $because = 'we want to add this rule for our software';
        $notDependOnClasses->evaluate($classDescription, $violations, $because);

        self::assertCount(1, $violations);
        $this->assertEquals(
            'depends on myNamespace\Banana, but should not depend on these namespaces: myNamespace because we want to add this rule for our software',
            $violations->get(0)->getError()
        );
    }

    public function test_it_should_return_false_if_depends_on_namespace(): void
    {
        $notDependOnClasses = new NotDependsOnTheseNamespaces('myNamespace');

        $classDescription = ClassDescription::build('HappyIsland\Myclass')
            ->addDependency(new ClassDependency('myNamespace\Banana', 0))
            ->addDependency(new ClassDependency('myNamespace\Mango', 10))
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $notDependOnClasses->evaluate($classDescription, $violations, $because);

        self::assertEquals(2, $violations->count());
        $this->assertEquals(
            'depends on myNamespace\Banana, but should not depend on these namespaces: myNamespace because we want to add this rule for our software',
            $violations->get(0)->getError()
        );
    }
}
