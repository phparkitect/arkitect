<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\IsNotTrait;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class IsNotTraitTest extends TestCase
{
    public function test_it_should_return_violation_error(): void
    {
        $isFinal = new IsNotTrait();

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->setTrait(true)
            ->build();

        $because = 'we want to add this rule for our software';
        $violationError = $isFinal->describe($classDescription, $because)->toString();

        $violations = new Violations();
        $isFinal->evaluate($classDescription, $violations, $because);

        self::assertNotEquals(0, $violations->count());
        self::assertEquals('HappyIsland should not be trait because we want to add this rule for our software', $violationError);
    }

    public function test_it_should_return_true_if_is_not_trait(): void
    {
        $isFinal = new IsNotTrait();

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $isFinal->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }
}
