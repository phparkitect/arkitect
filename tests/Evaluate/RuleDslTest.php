<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate;

use Arkitect\Evaluate\Constraint\IsFinal;
use Arkitect\Evaluate\Rule;
use Arkitect\Evaluate\Selector\HaveNameMatching;
use Arkitect\Evaluate\Selector\ResideInNamespace;
use Arkitect\Resolve\ParsedClassGraph;
use Arkitect\Tests\ParsedClassFixture;
use PHPUnit\Framework\TestCase;

final class RuleDslTest extends TestCase
{
    public function test_a_rule_reads_as_a_sentence(): void
    {
        $rule = Rule::allClasses()
            ->that(new ResideInNamespace('App\Domain'))
            ->should(new IsFinal())
            ->because('domain objects are not meant to be extended');

        $result = $rule->check([
            ParsedClassFixture::create('App\Domain\Order', isFinal: false),
            ParsedClassFixture::create('App\Infra\Db', isFinal: false),
        ], new ParsedClassGraph());

        self::assertSame(1, $result->checked);
        self::assertSame('App\Domain\Order', iterator_to_array($result->violations)[0]->fqcn);
    }

    /**
     * Selectors are added one at a time rather than as a list, so the chain
     * stays readable as it grows.
     */
    public function test_and_that_narrows_the_selection(): void
    {
        $rule = Rule::allClasses()
            ->that(new ResideInNamespace('App\Domain'))
            ->andThat(new HaveNameMatching('*Order'))
            ->should(new IsFinal())
            ->because('reasons');

        $result = $rule->check([
            ParsedClassFixture::create('App\Domain\PurchaseOrder', isFinal: false),
            ParsedClassFixture::create('App\Domain\Invoice', isFinal: false),
        ], new ParsedClassGraph());

        self::assertSame(1, $result->checked);
    }

    public function test_a_rule_without_a_selector_is_about_every_class(): void
    {
        $rule = Rule::allClasses()
            ->should(new IsFinal())
            ->because('everything is final here');

        $result = $rule->check([
            ParsedClassFixture::create('App\Domain\Order', isFinal: false),
            ParsedClassFixture::create('App\Infra\Db', isFinal: true),
        ], new ParsedClassGraph());

        self::assertSame(2, $result->checked);
        self::assertCount(1, $result->violations);
    }

    /**
     * The reason isn't decoration: the rule carries it so the report can say
     * why something is wrong, not just what.
     */
    public function test_the_rule_keeps_its_reason(): void
    {
        $rule = Rule::allClasses()->should(new IsFinal())->because('so it cannot be extended');

        self::assertSame('so it cannot be extended', $rule->because);
    }

    /**
     * There is no andShould, on purpose — a rule states one requirement, the
     * way a test makes one assertion. Two requirements are two rules, each
     * with its own reason, so a failure always says exactly what broke.
     */
    public function test_a_rule_states_exactly_one_requirement(): void
    {
        $draft = Rule::allClasses()->should(new IsFinal());

        $offered = array_values(array_diff(get_class_methods($draft), ['__construct']));

        self::assertSame(['because'], $offered);
    }
}
