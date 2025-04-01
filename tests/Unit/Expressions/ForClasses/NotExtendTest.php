<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\NotExtend;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class NotExtendTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $notExtend = new NotExtend('My\BaseClass');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->addExtends('My\BaseClass', 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violationError = $notExtend->describe($classDescription, $because)->toString();
        $violations = new Violations();

        $notExtend->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
        self::assertEquals('should not extend one of these classes: My\BaseClass because we want to add this rule for our software', $violationError);
    }

    public function test_it_should_not_return_violation_error_if_extends_another_class(): void
    {
        $notExtend = new NotExtend('My\BaseClass');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->addExtends('My\AnotherClass', 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();

        $notExtend->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_return_violation_error_for_multiple_extends(): void
    {
        $notExtend = new NotExtend('My\FirstExtend', 'My\SecondExtend');

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->addExtends('My\SecondExtend', 1)
            ->build();

        $because = 'we want to add this rule for our software';
        $violationError = $notExtend->describe($classDescription, $because)->toString();
        $violations = new Violations();

        $notExtend->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
        self::assertEquals('should not extend one of these classes: My\FirstExtend, My\SecondExtend because we want to add this rule for our software', $violationError);
    }
}
