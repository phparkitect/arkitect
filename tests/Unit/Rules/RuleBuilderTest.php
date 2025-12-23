<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Rules;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\ForClasses\Extend;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

/**
 * @see https://github.com/phparkitect/arkitect/issues/303
 */
class RuleBuilderTest extends TestCase
{
    public function test_reusing_that_for_multiple_should_produces_independent_rules(): void
    {
        $classViolatingBothRules = ClassDescription::getBuilder('App\Rector\MyClass', 'src/Rector/MyClass.php')
            ->build();

        $rectors = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Rector'));

        $nameMatchingRule = $rectors
            ->should(new HaveNameMatching('*Rector'))
            ->because('rector classes should have Rector suffix');

        $extendsRule = $rectors
            ->should(new Extend('Rector\Core\Rector\AbstractRector'))
            ->because('rector classes should extend AbstractRector');

        $nameMatchingViolations = new Violations();
        $nameMatchingRule->check($classViolatingBothRules, $nameMatchingViolations);

        $extendsViolations = new Violations();
        $extendsRule->check($classViolatingBothRules, $extendsViolations);

        self::assertCount(1, $nameMatchingViolations);
        self::assertCount(1, $extendsViolations);
        self::assertStringContainsString('should have a name that matches', $nameMatchingViolations->get(0)->getError());
        self::assertStringContainsString('should extend', $extendsViolations->get(0)->getError());
    }

    public function test_reusing_that_for_multiple_and_that_produces_independent_rules(): void
    {
        $classInBaseNamespaceOnly = ClassDescription::getBuilder('App\Service\MyService', 'src/Service/MyService.php')
            ->build();

        $services = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Service'));

        $internalRule = $services
            ->andThat(new ResideInOneOfTheseNamespaces('App\Service\Internal'))
            ->should(new HaveNameMatching('*Service'))
            ->because('internal services should have Service suffix');

        $externalRule = $services
            ->andThat(new ResideInOneOfTheseNamespaces('App\Service\External'))
            ->should(new HaveNameMatching('*Client'))
            ->because('external services should have Client suffix');

        $internalViolations = new Violations();
        $internalRule->check($classInBaseNamespaceOnly, $internalViolations);

        $externalViolations = new Violations();
        $externalRule->check($classInBaseNamespaceOnly, $externalViolations);

        self::assertCount(0, $internalViolations);
        self::assertCount(0, $externalViolations);
    }
}
