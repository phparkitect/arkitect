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

        $violations = (new IsA('App\Base'))->evaluate($child, $classGraph)->violations;

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

        $violations = (new IsA('App\Contract'))->evaluate($child, $classGraph)->violations;

        self::assertCount(0, $violations);
    }

    public function test_a_class_unrelated_to_the_target_produces_a_violation(): void
    {
        $class = ParsedClassFixture::create('App\Loner');
        $classGraph = new ClassGraph($class, ParsedClassFixture::create('App\Base'));

        $violations = (new IsA('App\Base'))->evaluate($class, $classGraph)->violations;

        self::assertCount(1, $violations);

        $violation = iterator_to_array($violations)[0];
        self::assertSame('App\Loner', $violation->fqcn);
        self::assertSame(IsA::class, $violation->constraint);
        self::assertSame('is not a App\Base', $violation->detail);
    }

    /**
     * The ancestor has source somewhere but wasn't in what we parsed, so the
     * chain can't be walked to an answer. That is a problem with the input,
     * not with the class, so it goes to its own channel rather than being
     * reported as a violation the user could try to fix — and, crucially,
     * rather than something a baseline could accept and then hide forever.
     */
    public function test_an_unresolvable_ancestor_chain_goes_to_the_unresolved_channel(): void
    {
        $class = ParsedClassFixture::create('App\Child', extends: ['Vendor\NeverParsed']);

        $outcome = (new IsA('App\Base'))->evaluate($class, new ClassGraph($class));

        self::assertCount(0, $outcome->violations);
        self::assertCount(1, $outcome->unresolved);

        $unresolved = iterator_to_array($outcome->unresolved)[0];
        self::assertSame('App\Child', $unresolved->fqcn);
        self::assertSame(
            'cannot be checked against App\Base: some ancestors were never parsed',
            $unresolved->detail
        );
    }
}
