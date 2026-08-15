<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate\Selector;

use Arkitect\Evaluate\Selector\Extend;
use Arkitect\Evaluate\Selector\Implement;
use Arkitect\Evaluate\Selector\IsA;
use Arkitect\Evaluate\Selector\Selection;
use Arkitect\Resolve\ParsedClassGraph;
use Arkitect\Tests\ParsedClassFixture;
use PHPUnit\Framework\TestCase;

/**
 * The selectors that walk the inheritance chain, and therefore the ones
 * that need a third answer. "Every class implementing X" is the shape most
 * real rules start from, and it couldn't be written until Selection existed.
 */
final class GraphSelectorsTest extends TestCase
{
    public function test_implement_selects_a_class_that_inherits_the_interface(): void
    {
        $class = ParsedClassFixture::create('App\Service', extends: ['App\Base']);
        $graph = new ParsedClassGraph($class, ParsedClassFixture::create('App\Base', implements: ['App\Contract']));

        self::assertSame(Selection::Yes, (new Implement('App\Contract'))->matches($class, $graph));
    }

    public function test_implement_does_not_select_an_unrelated_class(): void
    {
        $class = ParsedClassFixture::create('App\Loner');

        self::assertSame(Selection::No, (new Implement('App\Contract'))->matches($class, new ParsedClassGraph($class)));
    }

    public function test_is_a_selects_through_a_transitive_chain(): void
    {
        $class = ParsedClassFixture::create('App\Child', extends: ['App\Middle']);
        $graph = new ParsedClassGraph($class, ParsedClassFixture::create('App\Middle', extends: ['App\Base']));

        self::assertSame(Selection::Yes, (new IsA('App\Base'))->matches($class, $graph));
    }

    public function test_extend_does_not_select_on_an_implemented_interface(): void
    {
        $class = ParsedClassFixture::create('App\Service', implements: ['App\Contract']);

        self::assertSame(Selection::No, (new Extend('App\Contract'))->matches($class, new ParsedClassGraph($class)));
    }

    public function test_an_unparsed_ancestor_leaves_the_selection_unresolved(): void
    {
        $class = ParsedClassFixture::create('App\Child', extends: ['Vendor\NeverParsed']);

        self::assertSame(
            Selection::Unresolved,
            (new Implement('App\Contract'))->matches($class, new ParsedClassGraph($class))
        );
    }

    /**
     * The fix from stage 2 reaching selection: an internal ancestor is not
     * an unresolved one, so an exception class doesn't become undecidable.
     */
    public function test_an_internal_ancestor_does_not_leave_the_selection_unresolved(): void
    {
        $class = ParsedClassFixture::create('App\MyEx', extends: ['RuntimeException']);

        self::assertSame(Selection::No, (new Implement('App\Contract'))->matches($class, new ParsedClassGraph($class)));
    }
}
