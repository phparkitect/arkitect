<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate;

use Arkitect\Evaluate\Depth;
use Arkitect\Evaluate\Extend;
use Arkitect\Resolve\ClassGraph;
use Arkitect\Tests\ParsedClassFixture;
use PHPUnit\Framework\TestCase;

final class ExtendTest extends TestCase
{
    public function test_a_declared_parent_satisfies_the_default_transitive_check(): void
    {
        $class = ParsedClassFixture::create('App\Child', extends: ['App\Base']);

        $violations = (new Extend('App\Base'))->evaluate($class, new ClassGraph($class));

        self::assertCount(0, $violations);
    }

    public function test_a_grandparent_satisfies_the_transitive_check(): void
    {
        $class = ParsedClassFixture::create('App\Child', extends: ['App\Middle']);
        $graph = new ClassGraph($class, ParsedClassFixture::create('App\Middle', extends: ['App\Base']));

        $violations = (new Extend('App\Base'))->evaluate($class, $graph);

        self::assertCount(0, $violations);
    }

    /**
     * Extend follows the extends chain only. An interface reached through
     * `implements` is something the class *is*, not something it extends —
     * that question is IsA's, and conflating them would make Extend a
     * synonym for it.
     */
    public function test_an_implemented_interface_is_not_something_the_class_extends(): void
    {
        $class = ParsedClassFixture::create('App\Service', implements: ['App\Contract']);

        $violations = (new Extend('App\Contract'))->evaluate($class, new ClassGraph($class));

        self::assertCount(1, $violations);
        self::assertSame('does not extend App\Contract', iterator_to_array($violations)[0]->detail);
    }

    public function test_direct_depth_rejects_a_grandparent(): void
    {
        $class = ParsedClassFixture::create('App\Child', extends: ['App\Middle']);
        $graph = new ClassGraph($class, ParsedClassFixture::create('App\Middle', extends: ['App\Base']));

        $violations = (new Extend('App\Base', Depth::Direct))->evaluate($class, $graph);

        self::assertCount(1, $violations);
        self::assertSame('does not directly extend App\Base', iterator_to_array($violations)[0]->detail);
    }

    public function test_direct_depth_accepts_the_declared_parent(): void
    {
        $class = ParsedClassFixture::create('App\Child', extends: ['App\Base']);

        $violations = (new Extend('App\Base', Depth::Direct))->evaluate($class, new ClassGraph());

        self::assertCount(0, $violations);
    }

    /**
     * A class does not extend itself: unlike IsA, which is reflexive because
     * a class is a subtype of itself, the extends chain starts at the parent.
     */
    public function test_a_class_does_not_extend_itself(): void
    {
        $class = ParsedClassFixture::create('App\Base');

        $violations = (new Extend('App\Base'))->evaluate($class, new ClassGraph($class));

        self::assertCount(1, $violations);
    }

    public function test_a_declared_parent_that_was_never_parsed_still_resolves(): void
    {
        $class = ParsedClassFixture::create('App\Child', extends: ['Vendor\NeverParsed']);

        $violations = (new Extend('Vendor\NeverParsed'))->evaluate($class, new ClassGraph($class));

        self::assertCount(0, $violations);
    }

    public function test_an_unresolvable_chain_is_reported_rather_than_passing(): void
    {
        $class = ParsedClassFixture::create('App\Child', extends: ['Vendor\NeverParsed']);

        $violations = (new Extend('App\Base'))->evaluate($class, new ClassGraph($class));

        self::assertCount(1, $violations);
        self::assertSame(
            'cannot be resolved against App\Base: some ancestors were never parsed',
            iterator_to_array($violations)[0]->detail
        );
    }
}
