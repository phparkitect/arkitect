<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate\Constraint;

use Arkitect\Evaluate\Constraint\IsA;
use Arkitect\Resolve\ClassGraph;
use Arkitect\Tests\ParsedClassFixture;
use PHPUnit\Framework\TestCase;

final class IsATest extends TestCase
{
    public function test_a_class_that_extends_the_target_produces_no_violations(): void
    {
        $child = ParsedClassFixture::create('App\Child', extends: ['App\Base']);
        $classGraph = new ClassGraph($child, ParsedClassFixture::create('App\Base'));

        $violations = (new IsA('App\Base'))->evaluate($child, $classGraph);

        self::assertCount(0, $violations);
    }

    public function test_a_class_that_reaches_the_target_transitively_produces_no_violations(): void
    {
        $child = ParsedClassFixture::create('App\Child', extends: ['App\Middle']);
        $classGraph = new ClassGraph(
            $child,
            ParsedClassFixture::create('App\Middle', implements: ['App\Contract']),
            ParsedClassFixture::create('App\Contract'),
        );

        $violations = (new IsA('App\Contract'))->evaluate($child, $classGraph);

        self::assertCount(0, $violations);
    }

    public function test_a_class_unrelated_to_the_target_produces_a_violation(): void
    {
        $class = ParsedClassFixture::create('App\Loner');
        $classGraph = new ClassGraph($class, ParsedClassFixture::create('App\Base'));

        $violations = (new IsA('App\Base'))->evaluate($class, $classGraph);

        self::assertCount(1, $violations);

        $violation = iterator_to_array($violations)[0];
        self::assertSame('App\Loner', $violation->fqcn);
        self::assertSame(IsA::class, $violation->constraint);
        self::assertSame('is not a App\Base', $violation->detail);
    }

    /**
     * The ancestor exists but was never parsed, so the chain can't be walked
     * to an answer. Passing silently would hide an incomplete parse scope
     * (a missing vendor/, usually) behind a green run — see ARCHITECTURE.md,
     * Open: unknown ancestors are explicit rather than silently false.
     */
    public function test_an_unresolvable_ancestor_chain_is_reported_not_silently_passed(): void
    {
        $class = ParsedClassFixture::create('App\Child', extends: ['Vendor\NeverParsed']);
        $classGraph = new ClassGraph($class);

        $violations = (new IsA('App\Base'))->evaluate($class, $classGraph);

        self::assertCount(1, $violations);

        $violation = iterator_to_array($violations)[0];
        self::assertSame('cannot be resolved against App\Base: some ancestors were never parsed', $violation->detail);
    }
}
