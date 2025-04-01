<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Expression\ForClasses\IsNotFinal;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class IsNotFinalTest extends TestCase
{
    public function test_it_should_return_violation_if_is_final(): void
    {
        $isFinal = new IsNotFinal();

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName('HappyIsland')
            ->setFinal(true)
            ->build();

        $because = 'we want to add this rule for our software';
        $violations = new Violations();

        $isFinal->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
    }
}
