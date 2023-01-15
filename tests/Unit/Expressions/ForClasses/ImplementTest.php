<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class ImplementTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $interface = 'interface';

        $implementConstraint = new Implement($interface);
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [],
            null,
            false,
            false,
            false
        );

        $because = 'we want to add this rule for our software';
        $violationError = $implementConstraint->describe($classDescription, $because)->toString();

        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);
        self::assertNotEquals(0, $violations->count());

        $this->assertEquals('should implement '.$interface.' because we want to add this rule for our software', $violationError);
    }

    public function test_it_should_return_true_if_not_depends_on_namespace(): void
    {
        $interface = 'interface';

        $implementConstraint = new Implement($interface);
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [FullyQualifiedClassName::fromString('foo')],
            null,
            false,
            false,
            false
        );
        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);
        self::assertNotEquals(0, $violations->count());
    }

    public function test_it_should_return_false_if_depends_on_namespace(): void
    {
        $interface = 'interface';

        $implementConstraint = new Implement($interface);
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [FullyQualifiedClassName::fromString('interface')],
            null,
            false,
            false,
            false
        );
        $because = 'we want to add this rule for our software';

        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);
        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_check_the_complete_fqcn(): void
    {
        $interfaceName = '\Foo\Order';

        $implementConstraint = new Implement($interfaceName);
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [FullyQualifiedClassName::fromString('\Foo\Orderable')],
            null,
            false,
            false,
            false
        );
        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, '');
        self::assertEquals(1, $violations->count());
    }

    public function test_it_should_return_if_is_an_interface(): void
    {
        $interface = 'interface';

        $implementConstraint = new Implement($interface);
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [],
            null,
            false,
            false,
            true
        );
        $because = 'we want to add this rule for our software';

        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $because);
        self::assertEquals(0, $violations->count());
    }
}
