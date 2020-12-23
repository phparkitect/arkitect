<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\ForClasses\Implement;
use PHPUnit\Framework\TestCase;

class ImplementConstraintTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $interface = 'interface';

        $implementConstraint = new Implement($interface);
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            []
        );

        $violationError = $implementConstraint->describe($classDescription)->toString();

        $this->assertFalse($implementConstraint->evaluate($classDescription));
        $this->assertEquals('HappyIsland implements '.$interface, $violationError);
    }

    public function test_it_should_return_true_if_not_depends_on_namespace(): void
    {
        $interface = 'interface';

        $implementConstraint = new Implement($interface);
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [FullyQualifiedClassName::fromString('foo')]
        );

        $this->assertFalse($implementConstraint->evaluate($classDescription));
    }

    public function test_it_should_return_false_if_depends_on_namespace(): void
    {
        $interface = 'interface';

        $implementConstraint = new Implement($interface);
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [FullyQualifiedClassName::fromString('interface')]
        );

        $this->assertTrue($implementConstraint->evaluate($classDescription));
    }
}
