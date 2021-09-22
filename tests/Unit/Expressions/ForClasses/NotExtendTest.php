<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Expression\ForClasses\NotExtend;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class NotExtendTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $notExtend = new NotExtend('My\BaseClass');

        $classDescription = new ClassDescription(
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            [],
            FullyQualifiedClassName::fromString('My\BaseClass')
        );

        $violationError = $notExtend->describe($classDescription)->toString();

        $violations = new Violations();
        $notExtend->evaluate($classDescription, $violations);

        self::assertEquals(1, $violations->count());
        self::assertEquals('should not extend My\BaseClass', $violationError);
    }
}
