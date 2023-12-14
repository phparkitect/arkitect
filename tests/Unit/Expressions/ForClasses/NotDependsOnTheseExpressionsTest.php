<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Expression\Boolean\Andx;
use Arkitect\Expression\ForClasses\NotDependsOnTheseExpressions;
use Arkitect\Expression\ForClasses\NotResideInTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Violations;
use Arkitect\Tests\Fixtures\ComponentB\ClassBDependingOnAD;
use Arkitect\Tests\Unit\AbstractUnitTest;

class NotDependsOnTheseExpressionsTest extends AbstractUnitTest
{
    public function test_it_should_not_see_violations_in_exclusions(): void
    {
        $notDependOn = new NotDependsOnTheseExpressions(
            new Andx(
                new ResideInOneOfTheseNamespaces('Arkitect\Tests\Fixtures\ComponentC\\'),
                new NotResideInTheseNamespaces('Arkitect\Tests\Fixtures\ComponentC\ComponentCA\\')
            )
        );

        $classDescription = $this->getClassDescription(ClassBDependingOnAD::class);
        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $notDependOn->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_see_violations(): void
    {
        $notDependOn = new NotDependsOnTheseExpressions(
            new ResideInOneOfTheseNamespaces('Arkitect\Tests\Fixtures\ComponentA\\')
        );

        $classDescription = $this->getClassDescription(ClassBDependingOnAD::class);
        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $notDependOn->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
        $violationsText = $violations->toString();
        self::assertStringContainsString(
            'Arkitect\Tests\Fixtures\ComponentB\ClassBDependingOnAD has 1 violations',
            $violationsText
        );
        self::assertStringContainsString(
            "The dependency 'Arkitect\Tests\Fixtures\ComponentA\ClassAWithoutDependencies' violated the expression:",
            $violationsText
        );
        self::assertStringContainsString(
            'resides in one of these namespaces: Arkitect\Tests\Fixtures\ComponentA\\',
            $violationsText
        );
    }

    public function test_it_should_see_violations_only_outside_exclusions(): void
    {
        $notDependOn = new NotDependsOnTheseExpressions(
            new ResideInOneOfTheseNamespaces('Arkitect\Tests\Fixtures\ComponentA\\'),
            new Andx(
                new ResideInOneOfTheseNamespaces('Arkitect\Tests\Fixtures\ComponentC\\'),
                new NotResideInTheseNamespaces('Arkitect\Tests\Fixtures\ComponentC\ComponentCA\\')
            )
        );

        $classDescription = $this->getClassDescription(ClassBDependingOnAD::class);
        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $notDependOn->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
        $violationsText = $violations->toString();
        self::assertStringContainsString(
            'Arkitect\Tests\Fixtures\ComponentB\ClassBDependingOnAD has 1 violations',
            $violationsText
        );
        self::assertStringContainsString(
            "The dependency 'Arkitect\Tests\Fixtures\ComponentA\ClassAWithoutDependencies' violated the expression:",
            $violationsText
        );
        self::assertStringContainsString(
            <<<TXT
  NOT resides in one of these namespaces: Arkitect\Tests\Fixtures\ComponentA\
  OR
  NOT (
    resides in one of these namespaces: Arkitect\Tests\Fixtures\ComponentC\
    AND
    not resides in one of these namespaces: Arkitect\Tests\Fixtures\ComponentC\ComponentCA\
  )
TXT
            ,
            $violationsText
        );
    }
}
