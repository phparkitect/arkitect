<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit;

use Arkitect\DSL;
use Arkitect\Expression;
use Arkitect\Validation;
use PHPUnit\Framework\TestCase;

class DSLTest extends TestCase
{
    public function test_rule_with_single_that_clause(): void
    {
        $rule = DSL\Rule::classes()
            ->that(new Expression\ResideInNamespace('App\Controller'))
            ->should(new Expression\HaveNameEndingWith('Controller'))
            ->because('Controllers should have name ending with "Controller" suffix')
            ->get();

        $expected = new Validation\Rule(
            [
                new Expression\ResideInNamespace('App\Controller'),
            ],
            new Expression\HaveNameEndingWith('Controller'),
            'Controllers should have name ending with "Controller" suffix'
        );

        self::assertEquals($expected, $rule);
    }

    public function test_rule_with_two_that_clause(): void
    {
        $rule = DSL\Rule::classes()
            ->that(new Expression\ResideInNamespace('App\UI\Web\Controller', 'App\UI\Rest\Controller'))
                ->andThat(new Expression\AreInvokable())
            ->should(new Expression\HaveNameEndingWith('Controller'))
            ->because('Controllers should have name ending with "Controller" suffix')
            ->get();

        $expected = new Validation\Rule(
            [
                new Expression\ResideInNamespace('App\UI\Web\Controller', 'App\UI\Rest\Controller'),
                new Expression\AreInvokable(),
            ],
            new Expression\HaveNameEndingWith('Controller'),
            'Controllers should have name ending with "Controller" suffix'
        );

        self::assertEquals($expected, $rule);
    }

    public function test_rule_with_multiple_that_clause(): void
    {
        $rule = DSL\Rule::classes()
            ->that(new Expression\ResideInNamespace('App\UI\Web\Controller', 'App\UI\Rest\Controller'))
                ->andThat(new Expression\AreInvokable())
                ->andThat(new Expression\Extend('App\UI\BaseController'))
            ->should(new Expression\HaveNameEndingWith('Controller'))
            ->because('Controllers should have name ending with "Controller" suffix')
            ->get();

        $expected = new Validation\Rule(
            [
                new Expression\ResideInNamespace('App\UI\Web\Controller', 'App\UI\Rest\Controller'),
                new Expression\AreInvokable(),
                new Expression\Extend('App\UI\BaseController'),
            ],
            new Expression\HaveNameEndingWith('Controller'),
            'Controllers should have name ending with "Controller" suffix'
        );

        self::assertEquals($expected, $rule);
    }
}
