<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\ForClasses\Extend;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class ExtendTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $extend = new Extend('My\BaseClass');

        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [],
            FullyQualifiedClassName::fromString('My\AnotherClass'),
            false
        );

        $violationError = $extend->describe($classDescription)->toString();

        $violations = new Violations();
        $extend->evaluate($classDescription, $violations);

        self::assertEquals(1, $violations->count());
        self::assertEquals('should extend My\BaseClass', $violationError);
    }

    public function test_it_should_return_violation_error_if_extend_is_null(): void
    {
        $extend = new Extend('My\BaseClass');

        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [],
            null,
            false
        );

        $violationError = $extend->describe($classDescription)->toString();

        $violations = new Violations();
        $extend->evaluate($classDescription, $violations);

        self::assertEquals(1, $violations->count());
        self::assertEquals('should extend My\BaseClass', $violationError);
    }
}
