<?php

declare(strict_types=1);

namespace Arkitect\Tests\E2E\PHPUnit;

use Arkitect\ClassSet;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\IsAbstract;
use Arkitect\Expression\ForClasses\IsNotAbstract;
use Arkitect\Expression\ForClasses\IsNotEnum;
use Arkitect\Expression\ForClasses\IsNotFinal;
use Arkitect\Expression\ForClasses\IsNotInterface;
use Arkitect\Expression\ForClasses\IsNotReadonly;
use Arkitect\Expression\ForClasses\IsNotTrait;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;
use PHPUnit\Framework\TestCase;

class CheckClassWithMultipleExpressionsTest extends TestCase
{
    public function test_it_can_check_multiple_expressions(): void
    {
        $set = ClassSet::fromDir(__DIR__ . '/../_fixtures/happy_island');

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\BadCode'))
            ->andThat(new ResideInOneOfTheseNamespaces('App\HappyIsland'))
            ->should(new IsNotFinal())
            ->andShould(new IsNotReadonly())
            ->andShould(new IsNotAbstract())
            ->andShould(new IsNotEnum())
            ->andShould(new IsNotInterface())
            ->andShould(new IsNotTrait())
            ->because('some reason');

        ArchRuleTestCase::assertArchRule($rule, $set);
    }

    public function test_is_abstract_in_that(): void
    {
        $set = ClassSet::fromDir(__DIR__ . '/../_fixtures/is_something/meh');

        $rule = Rule::allClasses()
            ->that(new IsAbstract())
            ->should(new HaveNameMatching('*Abstract'))
            ->because('we want to prefix abstract classes');

        ArchRuleTestCase::assertArchRule($rule, $set);
    }

    public function test_is_abstract_in_should(): void
    {
        $set = ClassSet::fromDir(__DIR__ . '/../_fixtures/is_something/meh');

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App'))
            ->should(new IsAbstract())
            ->because('we want to prefix abstract classes');

        ArchRuleTestCase::assertArchRule($rule, $set);
    }

    public function test_is_abstract_in_should_cathes_final(): void
    {
        $set = ClassSet::fromDir(__DIR__ . '/../_fixtures/is_something/meh');

        $rule = Rule::allClasses()
            ->that(new HaveNameMatching('My*'))
            ->should(new IsAbstract())
            ->because('we want to prefix abstract classes');

        ArchRuleTestCase::assertArchRule($rule, $set);
    }
}
