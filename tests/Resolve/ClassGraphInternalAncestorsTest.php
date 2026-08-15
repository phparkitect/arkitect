<?php

declare(strict_types=1);

namespace Arkitect\Tests\Resolve;

use Arkitect\Resolve\ClassGraph;
use Arkitect\Resolve\Membership;
use Arkitect\Tests\ParsedClassFixture;
use PHPUnit\Framework\TestCase;

/**
 * An internal class has no PHP source, so it can never appear in the parsed
 * set no matter how much of the project is parsed. Treating that dead end
 * as Unknown made every class descending from one unanswerable — which is
 * every custom exception in every project, not an edge case.
 *
 * The inference that fixes it: an internal class only ever inherits from
 * other internal classes, because no C extension can name a user-defined
 * type. So a user-defined target is unreachable through an internal
 * ancestor, definitively.
 */
final class ClassGraphInternalAncestorsTest extends TestCase
{
    public function test_a_user_defined_target_is_unreachable_through_an_internal_ancestor(): void
    {
        $graph = new ClassGraph(ParsedClassFixture::create('App\MyEx', extends: ['RuntimeException']));

        self::assertSame(Membership::No, $graph->isA('App\MyEx', 'App\Marker'));
    }

    public function test_an_internal_target_reached_through_an_internal_ancestor_is_resolved(): void
    {
        $graph = new ClassGraph(ParsedClassFixture::create('App\MyEx', extends: ['RuntimeException']));

        self::assertSame(Membership::Yes, $graph->isA('App\MyEx', 'Throwable'));
    }

    public function test_an_interface_implemented_by_an_internal_ancestor_is_resolved(): void
    {
        $graph = new ClassGraph(ParsedClassFixture::create('App\MyList', extends: ['ArrayObject']));

        self::assertSame(Membership::Yes, $graph->isA('App\MyList', 'Countable'));
    }

    public function test_an_unrelated_internal_target_is_still_a_no(): void
    {
        $graph = new ClassGraph(ParsedClassFixture::create('App\MyEx', extends: ['RuntimeException']));

        self::assertSame(Membership::No, $graph->isA('App\MyEx', 'ArrayObject'));
    }

    /**
     * The case Unknown is supposed to be about, and now the only one left:
     * a parent that does have PHP source somewhere, which simply wasn't in
     * what we parsed — a missing vendor/, or an excluded path.
     */
    public function test_an_unparsed_user_defined_ancestor_is_still_unknown(): void
    {
        $graph = new ClassGraph(ParsedClassFixture::create('App\Child', extends: ['Vendor\NeverParsed']));

        self::assertSame(Membership::Unknown, $graph->isA('App\Child', 'App\Marker'));
    }

    public function test_an_internal_ancestor_chain_answers_has_ancestor_too(): void
    {
        $graph = new ClassGraph(ParsedClassFixture::create('App\MyEx', extends: ['RuntimeException']));

        self::assertSame(Membership::Yes, $graph->hasAncestor('App\MyEx', 'Exception'));
        self::assertSame(Membership::No, $graph->hasAncestor('App\MyEx', 'App\Base'));
    }

    /**
     * hasAncestor follows extends only, so an interface the internal
     * ancestor merely implements is not an ancestor of it.
     */
    public function test_an_interface_of_an_internal_ancestor_is_not_an_ancestor(): void
    {
        $graph = new ClassGraph(ParsedClassFixture::create('App\MyList', extends: ['ArrayObject']));

        self::assertSame(Membership::No, $graph->hasAncestor('App\MyList', 'Countable'));
    }
}
