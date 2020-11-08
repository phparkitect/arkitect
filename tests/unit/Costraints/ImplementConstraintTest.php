<?php

declare(strict_types=1);

namespace ArkitectTests\unit\Costraints;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Constraints\ImplementConstraint;
use PHPUnit\Framework\TestCase;

class ImplementConstraintTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $interface = 'interface';

        $implementConstraint = new ImplementConstraint($interface);
        $classDescription = new ClassDescription(
            'full/path',
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            []
        );

        $violationError = $implementConstraint->getViolationError($classDescription);

        $this->assertEquals('HappyIsland does not implement '.$interface, $violationError);
    }

    public function test_it_should_return_true_if_not_depends_on_namespace(): void
    {
        $interface = 'interface';

        $implementConstraint = new ImplementConstraint($interface);
        $classDescription = new ClassDescription(
            'full/path',
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [FullyQualifiedClassName::fromString('foo')]
        );

        $this->assertTrue($implementConstraint->isViolatedBy($classDescription));
    }

    public function test_it_should_return_false_if_depends_on_namespace(): void
    {
        $interface = 'interface';

        $implementConstraint = new ImplementConstraint($interface);
        $classDescription = new ClassDescription(
            'full/path',
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [FullyQualifiedClassName::fromString('interface')]
        );

        $this->assertFalse($implementConstraint->isViolatedBy($classDescription));
    }
}
