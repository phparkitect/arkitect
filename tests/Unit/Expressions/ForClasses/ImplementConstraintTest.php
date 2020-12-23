<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\ForClasses\Implement;
use PHPUnit\Framework\TestCase;

class ImplementConstraintTest extends TestCase
{
    public function testItShouldReturnViolationError(): void
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

    public function testItShouldReturnTrueIfNotDependsOnNamespace(): void
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

    public function testItShouldReturnFalseIfDependsOnNamespace(): void
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
