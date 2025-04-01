<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\IsNotEnum;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class IsNotEnumTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $isEnum = new IsNotEnum();

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')->setClassName('HappyIsland')
            ->setEnum(true)
            ->build();

        $because = 'we want to add this rule for our software';
        $violationError = $isEnum->describe($classDescription, $because)->toString();

        $violations = new Violations();
        $isEnum->evaluate($classDescription, $violations, $because);

        self::assertNotEquals(0, $violations->count());
        self::assertEquals('HappyIsland should not be an enum because we want to add this rule for our software', $violationError);
    }

    public function test_it_should_return_true_if_is_not_enum(): void
    {
        $isEnum = new IsNotEnum();

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')->setClassName('HappyIsland')
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $isEnum->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }
}
