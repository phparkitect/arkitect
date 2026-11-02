<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate;

use Arkitect\Evaluate\Constraint\IsFinal;
use Arkitect\Evaluate\Rule;
use Arkitect\Evaluate\Selector\ResideInNamespace;
use Arkitect\Resolve\ParsedClassGraph;
use Arkitect\Tests\ParsedClassFixture;
use PHPUnit\Framework\TestCase;

final class ExceptTest extends TestCase
{
    public function test_a_named_class_is_left_out_of_the_rule(): void
    {
        $result = Rule::allClasses()
            ->except('App\Domain\Order')
            ->that(new ResideInNamespace('App\Domain'))
            ->should(new IsFinal())
            ->because('domain objects are not meant to be extended')
            ->check([
                ParsedClassFixture::create('App\Domain\Order', isFinal: false),
                ParsedClassFixture::create('App\Domain\Invoice', isFinal: false),
            ], new ParsedClassGraph());

        self::assertCount(1, $result->violations);
        self::assertSame('App\Domain\Invoice', iterator_to_array($result->violations)[0]->fqcn);
    }

    /**
     * An excluded class is not selected either, or a rule about everything
     * but one class would report having looked at it.
     */
    public function test_an_excluded_class_is_not_counted_as_selected(): void
    {
        $result = Rule::allClasses()
            ->except('App\Domain\Order')
            ->should(new IsFinal())
            ->because('everything is final')
            ->check([
                ParsedClassFixture::create('App\Domain\Order', isFinal: false),
            ], new ParsedClassGraph());

        self::assertSame(0, $result->selected);
        self::assertCount(0, $result->violations);
    }

    public function test_several_classes_can_be_named_at_once(): void
    {
        $result = Rule::allClasses()
            ->except('App\Domain\Order', 'App\Domain\Invoice')
            ->should(new IsFinal())
            ->because('everything is final')
            ->check([
                ParsedClassFixture::create('App\Domain\Order', isFinal: false),
                ParsedClassFixture::create('App\Domain\Invoice', isFinal: false),
                ParsedClassFixture::create('App\Domain\Shipment', isFinal: false),
            ], new ParsedClassGraph());

        self::assertCount(1, $result->violations);
        self::assertSame('App\Domain\Shipment', iterator_to_array($result->violations)[0]->fqcn);
    }

    /**
     * The reason whole names are the only thing accepted: this is always a
     * typo, and a pattern that matched nothing would not be.
     */
    public function test_a_name_no_class_answers_to_is_reported(): void
    {
        $result = Rule::allClasses()
            ->except('App\Domain\Ordr')
            ->should(new IsFinal())
            ->because('everything is final')
            ->check([
                ParsedClassFixture::create('App\Domain\Order', isFinal: true),
            ], new ParsedClassGraph());

        self::assertSame(['App\Domain\Ordr'], $result->missingExclusions);
    }

    public function test_a_name_a_class_answers_to_is_not_reported(): void
    {
        $result = Rule::allClasses()
            ->except('App\Domain\Order')
            ->should(new IsFinal())
            ->because('everything is final')
            ->check([
                ParsedClassFixture::create('App\Domain\Order', isFinal: false),
            ], new ParsedClassGraph());

        self::assertSame([], $result->missingExclusions);
    }

    /**
     * Existence is checked against every class the rule was given, not
     * against the ones it selected: excluding a class the that() already
     * filters out is redundant, not a mistake.
     */
    public function test_a_class_outside_the_selection_still_counts_as_existing(): void
    {
        $result = Rule::allClasses()
            ->except('App\Infra\Db')
            ->that(new ResideInNamespace('App\Domain'))
            ->should(new IsFinal())
            ->because('domain objects are not meant to be extended')
            ->check([
                ParsedClassFixture::create('App\Domain\Order', isFinal: true),
                ParsedClassFixture::create('App\Infra\Db', isFinal: false),
            ], new ParsedClassGraph());

        self::assertSame([], $result->missingExclusions);
    }

    public function test_a_leading_separator_names_the_same_class(): void
    {
        $result = Rule::allClasses()
            ->except('\App\Domain\Order')
            ->should(new IsFinal())
            ->because('everything is final')
            ->check([
                ParsedClassFixture::create('App\Domain\Order', isFinal: false),
            ], new ParsedClassGraph());

        self::assertCount(0, $result->violations);
        self::assertSame([], $result->missingExclusions);
    }

    public function test_a_wildcard_is_not_a_class_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Rule::allClasses()->except('App\Domain\*');
    }

    public function test_a_namespace_is_accepted_as_a_name_and_matches_no_class(): void
    {
        $result = Rule::allClasses()
            ->except('App\Domain')
            ->should(new IsFinal())
            ->because('everything is final')
            ->check([
                ParsedClassFixture::create('App\Domain\Order', isFinal: true),
            ], new ParsedClassGraph());

        self::assertSame(['App\Domain'], $result->missingExclusions);
    }
}
