<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\Boolean;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\Boolean\Andx;
use Arkitect\Expression\ForClasses\Extend;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class AndxTest extends TestCase
{
    public function test_it_should_pass_the_rule(): void
    {
        $interface = 'interface';
        $class = 'SomeClass';
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [FullyQualifiedClassName::fromString($interface)],
            FullyQualifiedClassName::fromString($class),
            false,
            false,
            false,
            false,
            false
        );
        $implementConstraint = new Implement($interface);
        $extendsConstraint = new Extend($class);
        $andConstraint = new Andx($implementConstraint, $extendsConstraint);

        $because = 'reasons';
        $violations = new Violations();
        $andConstraint->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_pass_the_rule_when_and_is_empty(): void
    {
        $interface = 'interface';
        $class = 'SomeClass';
        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [FullyQualifiedClassName::fromString($interface)],
            FullyQualifiedClassName::fromString($class),
            false,
            false,
            false,
            false,
            false
        );
        $andConstraint = new Andx();

        $because = 'reasons';
        $violations = new Violations();
        $andConstraint->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_not_pass_the_rule(): void
    {
        $interface = 'SomeInterface';
        $class = 'SomeClass';

        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [FullyQualifiedClassName::fromString($interface)],
            null,
            false,
            false,
            false,
            false,
            false
        );

        $implementConstraint = new Implement($interface);
        $extendsConstraint = new Extend($class);
        $andConstraint = new Andx($implementConstraint, $extendsConstraint);

        $because = 'reasons';
        $violationError = $andConstraint->describe($classDescription, $because)->toString();

        $violations = new Violations();
        $andConstraint->evaluate($classDescription, $violations, $because);
        self::assertNotEquals(0, $violations->count());

        $this->assertEquals(
            'all expressions must be true (should implement SomeInterface, should extend SomeClass) because reasons',
            $violationError
        );
        $this->assertEquals(
            "The class 'HappyIsland' violated the expression should extend SomeClass, but "
            .'all expressions must be true (should implement SomeInterface, should extend SomeClass) because reasons',
            $violations->get(0)->getError()
        );
    }
}
