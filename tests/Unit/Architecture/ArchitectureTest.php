<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Architecture;

use Arkitect\Expression\ForClasses\DependsOnlyOnTheseExpressions;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\RuleBuilders\Architecture\Architecture;
use Arkitect\Rules\Rule;
use PHPUnit\Framework\TestCase;

class ArchitectureTest extends TestCase
{
    public function test_layered_architecture(): void
    {
        $rules = Architecture::withComponents()
            ->component('Domain')->definedBy('App\*\Domain\*')
            ->component('Application')->definedBy('App\*\Application\*')
            ->component('Infrastructure')->definedBy('App\*\Infrastructure\*')

            ->where('Domain')->shouldNotDependOnAnyComponent()
            ->where('Application')->mayDependOnComponents('Domain')
            ->where('Infrastructure')->mayDependOnAnyComponent()

            ->rules();

        $expectedRules = [
            Rule::allClasses()
                ->that(new ResideInOneOfTheseNamespaces('App\*\Domain\*'))
                ->should(new DependsOnlyOnTheseExpressions(
                    new ResideInOneOfTheseNamespaces('App\*\Domain\*')
                ))
                ->because('of component architecture'),
            Rule::allClasses()
                ->that(new ResideInOneOfTheseNamespaces('App\*\Application\*'))
                ->should(new DependsOnlyOnTheseExpressions(
                    new ResideInOneOfTheseNamespaces('App\*\Application\*', 'App\*\Domain\*')
                ))
                ->because('of component architecture'),
            Rule::allClasses()
                ->that(new ResideInOneOfTheseNamespaces('App\*\Infrastructure\*'))
                ->should(new DependsOnlyOnTheseExpressions(
                    new ResideInOneOfTheseNamespaces('App\*\Infrastructure\*', 'App\*\Domain\*', 'App\*\Application\*')
                ))
                ->because('of component architecture'),
        ];

        $actualRules = iterator_to_array($rules);
        self::assertEquals($expectedRules, $actualRules);
    }

    public function test_layered_architecture_with_expression(): void
    {
        $rules = Architecture::withComponents()
            ->component('Domain')->definedByExpression(new ResideInOneOfTheseNamespaces('App\*\Domain\*'))
            ->component('Application')->definedByExpression(new ResideInOneOfTheseNamespaces('App\*\Application\*'))
            ->component('Infrastructure')
                ->definedByExpression(new ResideInOneOfTheseNamespaces('App\*\Infrastructure\*'))

            ->where('Domain')->shouldNotDependOnAnyComponent()
            ->where('Application')->mayDependOnComponents('Domain')
            ->where('Infrastructure')->mayDependOnAnyComponent()

            ->rules();

        $expectedRules = [
            Rule::allClasses()
                ->that(new ResideInOneOfTheseNamespaces('App\*\Domain\*'))
                ->should(new DependsOnlyOnTheseExpressions(
                    new ResideInOneOfTheseNamespaces('App\*\Domain\*')
                ))
                ->because('of component architecture'),
            Rule::allClasses()
                ->that(new ResideInOneOfTheseNamespaces('App\*\Application\*'))
                ->should(new DependsOnlyOnTheseExpressions(
                    new ResideInOneOfTheseNamespaces('App\*\Application\*', 'App\*\Domain\*')
                ))
                ->because('of component architecture'),
            Rule::allClasses()
                ->that(new ResideInOneOfTheseNamespaces('App\*\Infrastructure\*'))
                ->should(new DependsOnlyOnTheseExpressions(
                    new ResideInOneOfTheseNamespaces('App\*\Infrastructure\*', 'App\*\Domain\*', 'App\*\Application\*')
                ))
                ->because('of component architecture'),
        ];

        $actualRules = iterator_to_array($rules);
        self::assertEquals($expectedRules, $actualRules);
    }

    public function test_layered_architecture_with_mix_of_namespace_and_expression(): void
    {
        $rules = Architecture::withComponents()
            ->component('Domain')->definedByExpression(new ResideInOneOfTheseNamespaces('App\*\Domain\*'))
            ->component('Application')->definedByExpression(new ResideInOneOfTheseNamespaces('App\*\Application\*'))
            ->component('Infrastructure')->definedBy('App\*\Infrastructure\*')

            ->where('Domain')->shouldNotDependOnAnyComponent()
            ->where('Application')->mayDependOnComponents('Domain')
            ->where('Infrastructure')->mayDependOnAnyComponent()

            ->rules();

        $expectedRules = [
            Rule::allClasses()
                ->that(new ResideInOneOfTheseNamespaces('App\*\Domain\*'))
                ->should(new DependsOnlyOnTheseExpressions(
                    new ResideInOneOfTheseNamespaces('App\*\Domain\*')
                ))
                ->because('of component architecture'),
            Rule::allClasses()
                ->that(new ResideInOneOfTheseNamespaces('App\*\Application\*'))
                ->should(new DependsOnlyOnTheseExpressions(
                    new ResideInOneOfTheseNamespaces('App\*\Application\*', 'App\*\Domain\*')
                ))
                ->because('of component architecture'),
            Rule::allClasses()
                ->that(new ResideInOneOfTheseNamespaces('App\*\Infrastructure\*'))
                ->should(new DependsOnlyOnTheseExpressions(
                    new ResideInOneOfTheseNamespaces('App\*\Domain\*', 'App\*\Application\*', 'App\*\Infrastructure\*')
                ))
                ->because('of component architecture'),
        ];

        $actualRules = iterator_to_array($rules);
        self::assertEquals($expectedRules, $actualRules);
    }

    public function test_layered_architecture_with_depends_only_on_components(): void
    {
        $rules = Architecture::withComponents()
            ->component('Domain')->definedBy('App\*\Domain\*')
            ->where('Domain')->shouldOnlyDependOnComponents('Domain')

            ->rules();

        $expectedRules = [
            Rule::allClasses()
                ->that(new ResideInOneOfTheseNamespaces('App\*\Domain\*'))
                ->should(new DependsOnlyOnTheseExpressions(new ResideInOneOfTheseNamespaces('App\*\Domain\*')))
                ->because('of component architecture'),
        ];

        self::assertEquals($expectedRules, iterator_to_array($rules));
    }
}
