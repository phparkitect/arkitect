<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate\Selector;

use Arkitect\Evaluate\Selector\HaveNameMatching;
use Arkitect\Evaluate\Selector\Selection;
use Arkitect\Resolve\ClassGraph;
use Arkitect\Tests\ParsedClassFixture;
use PHPUnit\Framework\TestCase;

final class HaveNameMatchingTest extends TestCase
{
    public function test_a_matching_name_is_selected(): void
    {
        $class = ParsedClassFixture::create('App\Http\UserController');

        self::assertSame(Selection::Yes, (new HaveNameMatching('*Controller'))->matches($class, new ClassGraph()));
    }

    public function test_a_name_that_does_not_match_is_not_selected(): void
    {
        $class = ParsedClassFixture::create('App\Http\UserRepository');

        self::assertSame(Selection::No, (new HaveNameMatching('*Controller'))->matches($class, new ClassGraph()));
    }

    /**
     * The pattern sees the short name only, so a namespace can't select a
     * class by accident.
     */
    public function test_the_namespace_is_not_part_of_what_the_pattern_sees(): void
    {
        $class = ParsedClassFixture::create('App\ControllerSupport\Order');

        self::assertSame(Selection::No, (new HaveNameMatching('*Controller'))->matches($class, new ClassGraph()));
    }
}
