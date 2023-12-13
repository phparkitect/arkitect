<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\ForClasses\NotImplementFromNamespace;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class NotImplementFromNamespaceTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $notExtend = new NotImplementFromNamespace('My');

        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [FullyQualifiedClassName::fromString('My\BaseClass')],
            null,
            false,
            false,
            false,
            false,
            false
        );
        $because = 'we want to add this rule for our software';
        $violationError = $notExtend->describe($classDescription, $because)->toString();

        $violations = new Violations();
        $notExtend->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
        self::assertEquals("should not implement from namespace My\nbecause we want to add this rule for our software", $violationError);
    }

    public function test_it_should_not_return_violation_error_if_implements_another_interface(): void
    {
        $notExtend = new NotImplementFromNamespace('My');

        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [FullyQualifiedClassName::fromString('Other\AnotherClass')],
            null,
            false,
            false,
            false,
            false,
            false
        );

        $violations = new Violations();
        $notExtend->evaluate($classDescription, $violations);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_not_return_violation_error_if_implements_from_exclusion_list(): void
    {
        $notExtend = new NotImplementFromNamespace('My', ['My\Yet']);

        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [FullyQualifiedClassName::fromString('My\Yet\AnotherClass')],
            null,
            false,
            false,
            false,
            false,
            false
        );

        $violations = new Violations();
        $notExtend->evaluate($classDescription, $violations);

        self::assertEquals(0, $violations->count());
    }
}
