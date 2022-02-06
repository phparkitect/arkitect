<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\ClassDescriptionCollection;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\ForClasses\NotImplement;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class NotImplementTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $interface = 'interface';

        $implementConstraint = new NotImplement($interface);
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [],
            null
        );

        $classDescriptionCollection = new ClassDescriptionCollection();
        $classDescriptionCollection->add($classDescription);

        $violationError = $implementConstraint->describe($classDescription)->toString();

        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $classDescriptionCollection);
        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_true_if_not_depends_on_namespace(): void
    {
        $interface = 'interface';

        $implementConstraint = new NotImplement($interface);
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [FullyQualifiedClassName::fromString('foo')],
            null
        );

        $classDescriptionCollection = new ClassDescriptionCollection();
        $classDescriptionCollection->add($classDescription);
        $classDescriptionCollection->add(ClassDescription::build('foo')->get());

        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $classDescriptionCollection);
        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_false_if_depends_on_namespace(): void
    {
        $interface = 'interface';

        $implementConstraint = new NotImplement($interface);
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [FullyQualifiedClassName::fromString('interface')],
            null
        );

        $classDescriptionCollection = new ClassDescriptionCollection();
        $classDescriptionCollection->add($classDescription);
        $classDescriptionCollection->add(ClassDescription::build('interface')->get());

        $violations = new Violations();
        $implementConstraint->evaluate($classDescription, $violations, $classDescriptionCollection);

        $violationError = $implementConstraint->describe($classDescription)->toString();
        self::assertNotEquals(0, $violations->count());

        $this->assertEquals('should not implement '.$interface, $violationError);
    }
}
