<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\ForClasses\Extend;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class ExtendTest extends TestCase
{
    public function test_it_should_return_no_violation_on_success(): void
    {
        $extend = new Extend('My\BaseClass');

        $builder = new ClassDescriptionBuilder();
        $builder->setClassName('My\Class');
        $builder->setExtends('My\BaseClass', 10);

        $classDescription = $builder->build();

        $violations = new Violations();
        $extend->evaluate($classDescription, $violations, 'because');

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_violation_error(): void
    {
        $extend = new Extend('My\BaseClass');

        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [],
            FullyQualifiedClassName::fromString('My\AnotherClass'),
            false,
            false,
            false
        );

        $because = 'we want to add this rule for our software';
        $violationError = $extend->describe($classDescription, $because)->toString();

        $violations = new Violations();
        $extend->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
        self::assertEquals('should extend My\BaseClass because we want to add this rule for our software', $violationError);
    }

    public function test_it_should_return_violation_error_if_extend_is_null(): void
    {
        $extend = new Extend('My\BaseClass');

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
        $violationError = $extend->describe($classDescription, $because)->toString();

        $violations = new Violations();
        $extend->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
        self::assertEquals('should extend My\BaseClass because we want to add this rule for our software', $violationError);
    }
}
