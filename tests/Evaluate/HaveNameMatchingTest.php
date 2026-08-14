<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate;

use Arkitect\Evaluate\HaveNameMatching;
use Arkitect\Resolve\ClassGraph;
use Arkitect\Tests\ParsedClassFixture;
use PHPUnit\Framework\TestCase;

final class HaveNameMatchingTest extends TestCase
{
    public function test_a_matching_suffix_produces_no_violations(): void
    {
        $class = ParsedClassFixture::create('App\Http\UserController');

        self::assertCount(0, (new HaveNameMatching('*Controller'))->evaluate($class, new ClassGraph()));
    }

    public function test_a_name_that_does_not_match_produces_a_violation(): void
    {
        $class = ParsedClassFixture::create('App\Http\UserRepository');

        $violations = (new HaveNameMatching('*Controller'))->evaluate($class, new ClassGraph());

        self::assertCount(1, $violations);

        $violation = iterator_to_array($violations)[0];
        self::assertSame(HaveNameMatching::class, $violation->expression);
        self::assertSame('does not have a name matching *Controller', $violation->detail);
    }

    /**
     * The pattern applies to the short name, so the namespace can't satisfy
     * it by accident — a class named Order in App\ControllerSupport is not
     * a Controller.
     */
    public function test_the_namespace_is_not_part_of_what_the_pattern_sees(): void
    {
        $class = ParsedClassFixture::create('App\ControllerSupport\Order');

        self::assertCount(1, (new HaveNameMatching('*Controller'))->evaluate($class, new ClassGraph()));
    }

    public function test_a_class_in_the_global_namespace_is_matched_by_its_bare_name(): void
    {
        $class = ParsedClassFixture::create('UserController');

        self::assertCount(0, (new HaveNameMatching('*Controller'))->evaluate($class, new ClassGraph()));
    }
}
