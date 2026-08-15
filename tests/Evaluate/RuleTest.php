<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate;

use Arkitect\Evaluate\Constraint\IsA;
use Arkitect\Evaluate\Constraint\IsFinal;
use Arkitect\Evaluate\Constraint\IsReadonly;
use Arkitect\Evaluate\Rule;
use Arkitect\Evaluate\Selector\HaveNameMatching;
use Arkitect\Evaluate\Selector\ResideInNamespace;
use Arkitect\Resolve\ClassGraph;
use Arkitect\Tests\ParsedClassFixture;
use PHPUnit\Framework\TestCase;

final class RuleTest extends TestCase
{
    public function test_a_rule_checks_only_the_classes_its_selector_matches(): void
    {
        $rule = new Rule([new ResideInNamespace('App\Domain')], [new IsFinal()]);

        $result = $rule->check([
            ParsedClassFixture::create('App\Domain\Order', isFinal: false),
            ParsedClassFixture::create('App\Infra\Db', isFinal: false),
        ], new ClassGraph());

        self::assertSame(1, $result->checked);
        self::assertCount(1, $result->violations);
        self::assertSame('App\Domain\Order', iterator_to_array($result->violations)[0]->fqcn);
    }

    /**
     * The distinction the report requirement rests on: both runs found no
     * violations, and only one of them actually checked anything. Without
     * the count, a rule whose selector matches nothing looks identical to a
     * codebase that satisfies it.
     */
    public function test_matching_nothing_is_distinguishable_from_a_clean_run(): void
    {
        $matchedNothing = (new Rule([new ResideInNamespace('App\Nowhere')], [new IsFinal()]))
            ->check([ParsedClassFixture::create('App\Domain\Order', isFinal: true)], new ClassGraph());

        $clean = (new Rule([new ResideInNamespace('App\Domain')], [new IsFinal()]))
            ->check([ParsedClassFixture::create('App\Domain\Order', isFinal: true)], new ClassGraph());

        self::assertCount(0, $matchedNothing->violations);
        self::assertCount(0, $clean->violations);

        self::assertTrue($matchedNothing->matchedNothing());
        self::assertFalse($clean->matchedNothing());
    }

    public function test_several_selectors_all_have_to_match(): void
    {
        $rule = new Rule(
            [new ResideInNamespace('App\Domain'), new HaveNameMatching('*Order')],
            [new IsFinal()]
        );

        $result = $rule->check([
            ParsedClassFixture::create('App\Domain\PurchaseOrder', isFinal: false),
            ParsedClassFixture::create('App\Domain\Invoice', isFinal: false),
            ParsedClassFixture::create('App\Infra\StoredOrder', isFinal: false),
        ], new ClassGraph());

        self::assertSame(1, $result->checked);
        self::assertSame('App\Domain\PurchaseOrder', iterator_to_array($result->violations)[0]->fqcn);
    }

    public function test_a_rule_without_selectors_checks_every_class(): void
    {
        $rule = new Rule([], [new IsFinal()]);

        $result = $rule->check([
            ParsedClassFixture::create('App\Domain\Order', isFinal: false),
            ParsedClassFixture::create('App\Infra\Db', isFinal: true),
        ], new ClassGraph());

        self::assertSame(2, $result->checked);
        self::assertCount(1, $result->violations);
    }

    public function test_every_constraint_is_evaluated_against_every_selected_class(): void
    {
        $rule = new Rule([], [new IsFinal(), new IsReadonly()]);

        $result = $rule->check([
            ParsedClassFixture::create('App\Domain\Order', isFinal: false, isReadonly: false),
        ], new ClassGraph());

        self::assertCount(2, $result->violations);

        $details = array_map(static fn ($v) => $v->detail, iterator_to_array($result->violations));
        self::assertSame(['is not final', 'is not readonly'], $details);
    }

    /**
     * A rule that couldn't look at part of what it was asked about has not
     * passed, even with an empty violation list — so the two are carried
     * separately and `isConclusive()` is what says whether the answer can
     * be trusted.
     */
    public function test_unresolved_classes_are_carried_apart_from_violations(): void
    {
        $unresolvable = ParsedClassFixture::create('App\Child', extends: ['Vendor\NeverParsed']);

        $result = (new Rule([], [new IsA('App\Contract')]))
            ->check([$unresolvable], new ClassGraph($unresolvable));

        self::assertSame(1, $result->checked);
        self::assertCount(0, $result->violations);
        self::assertCount(1, $result->unresolved);
        self::assertFalse($result->isConclusive());
    }

    public function test_a_rule_that_resolved_everything_is_conclusive(): void
    {
        $result = (new Rule([], [new IsFinal()]))
            ->check([ParsedClassFixture::create('App\Order', isFinal: false)], new ClassGraph());

        self::assertCount(1, $result->violations);
        self::assertTrue($result->isConclusive());
    }

    public function test_an_empty_class_set_matches_nothing(): void
    {
        $result = (new Rule([], [new IsFinal()]))->check([], new ClassGraph());

        self::assertTrue($result->matchedNothing());
        self::assertCount(0, $result->violations);
    }
}
