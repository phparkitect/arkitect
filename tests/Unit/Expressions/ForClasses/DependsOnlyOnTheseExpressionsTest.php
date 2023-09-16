<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FileParserFactory;
use Arkitect\Expression\ForClasses\DependsOnlyOnTheseExpressions;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class DependsOnlyOnTheseExpressionsTest extends TestCase
{
    public function test_it_should_have_no_violations_if_it_has_no_dependencies(): void
    {
        $dependsOnlyOnTheseExpressions = new DependsOnlyOnTheseExpressions(new ResideInOneOfTheseNamespaces('myNamespace'));

        $classDescription = ClassDescription::getBuilder('HappyIsland\Myclass')->build();
        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $dependsOnlyOnTheseExpressions->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_have_no_violations_if_it_has_no_dependencies_outside_expression(): void
    {
        $dependsOnlyOnTheseExpressions = new DependsOnlyOnTheseExpressions(new ResideInOneOfTheseNamespaces('Arkitect\Tests\Fixtures\Fruit'));

        $classDescription = $this->getClassDescription(file_get_contents(\FIXTURES_PATH.'/Fruit/Banana.php'));

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $dependsOnlyOnTheseExpressions->evaluate($classDescription, $violations, $because);

        self::assertEquals(0, $violations->count());
    }

    public function test_it_should_have_violations_if_it_has_dependencies_outside_expression(): void
    {
        $dependsOnlyOnTheseExpressions = new DependsOnlyOnTheseExpressions(new ResideInOneOfTheseNamespaces('Arkitect\Tests\Fixtures\Fruit'));

        $classDescription = $this->getClassDescription(file_get_contents(\FIXTURES_PATH.'/Fruit/AnimalFruit.php'));

        $because = 'we want to add this rule for our software';
        $violations = new Violations();
        $dependsOnlyOnTheseExpressions->evaluate($classDescription, $violations, $because);

        self::assertEquals(1, $violations->count());
    }

    private function getClassDescription(string $classCode, string $fileName = 'MyClass.php'): ClassDescription
    {
        $fileParser = FileParserFactory::createFileParser();
        $fileParser->parse($classCode, $fileName);
        $classDescriptionList = $fileParser->getClassDescriptions();

        return array_pop($classDescriptionList);
    }
}
