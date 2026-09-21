<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate;

use Arkitect\Evaluate\Pattern;
use PHPUnit\Framework\TestCase;

final class PatternTest extends TestCase
{
    public function test_a_pattern_without_wildcards_matches_the_exact_name(): void
    {
        self::assertTrue((new Pattern('App\Domain\Order'))->matches('App\Domain\Order'));
    }

    public function test_matching_a_name_does_not_reach_beneath_it(): void
    {
        self::assertFalse((new Pattern('App\Domain'))->matches('App\Domain\Order'));
    }

    public function test_a_star_stays_inside_one_segment(): void
    {
        $pattern = new Pattern('App\*\Order');

        self::assertTrue($pattern->matches('App\Domain\Order'));
        self::assertFalse($pattern->matches('App\Domain\Nested\Order'));
    }

    public function test_a_double_star_stands_for_any_number_of_segments_including_none(): void
    {
        $pattern = new Pattern('App\**\Order');

        self::assertTrue($pattern->matches('App\Order'));
        self::assertTrue($pattern->matches('App\Domain\Order'));
        self::assertTrue($pattern->matches('App\Domain\Nested\Order'));
    }

    public function test_a_star_can_stand_for_part_of_a_segment(): void
    {
        $pattern = new Pattern('Arkitect\Evaluate\Violation*');

        self::assertTrue($pattern->matches('Arkitect\Evaluate\Violation'));
        self::assertTrue($pattern->matches('Arkitect\Evaluate\Violations'));
        self::assertFalse($pattern->matches('Arkitect\Evaluate\Violation\Thing'));
    }

    public function test_a_name_suffix_anywhere_takes_a_double_star(): void
    {
        self::assertFalse((new Pattern('*Controller'))->matches('App\Http\UserController'));
        self::assertTrue((new Pattern('**\*Controller'))->matches('App\Http\UserController'));
    }

    public function test_a_namespace_contains_the_classes_declared_in_it_and_beneath_it(): void
    {
        $pattern = new Pattern('App\Domain');

        self::assertTrue($pattern->contains('App\Domain\Order'));
        self::assertTrue($pattern->contains('App\Domain\Deeply\Nested\Thing'));
    }

    /**
     * PHP lets a class and a namespace share a name, and a rule about
     * namespaces means the namespace.
     */
    public function test_a_namespace_does_not_contain_the_class_that_shares_its_name(): void
    {
        self::assertFalse((new Pattern('App\Domain'))->contains('App\Domain'));
    }

    public function test_a_namespace_does_not_contain_a_sibling_sharing_its_prefix(): void
    {
        self::assertFalse((new Pattern('App\Domain'))->contains('App\DomainEvents\Something'));
    }

    public function test_a_wildcard_namespace_contains_what_is_beneath_it_too(): void
    {
        $pattern = new Pattern('App\*\Domain');

        self::assertTrue($pattern->contains('App\Billing\Domain\Invoice'));
        self::assertTrue($pattern->contains('App\Billing\Domain\Model\Invoice'));
        self::assertFalse($pattern->contains('App\Modules\Billing\Domain\Invoice'));
    }

    public function test_a_star_namespace_does_not_contain_what_is_declared_directly_above_it(): void
    {
        self::assertFalse((new Pattern('App\*'))->contains('App\Kernel'));
        self::assertTrue((new Pattern('App\*'))->contains('App\Http\Kernel'));
    }

    public function test_a_trailing_separator_is_accepted_and_means_the_same(): void
    {
        self::assertTrue((new Pattern('App\Domain\\'))->contains('App\Domain\Order'));
    }

    /**
     * A pattern nothing can ever match is a config mistake, not a rule that
     * silently passes.
     */
    public function test_an_empty_pattern_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Pattern('');
    }

    public function test_a_regex_is_rejected_as_a_pattern(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Pattern('/^App\\\\.*/');
    }

    public function test_a_question_mark_is_not_a_wildcard(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Pattern('App\V?\Thing');
    }

    public function test_a_double_star_inside_a_segment_is_rejected(): void
    {
        $this->expectExceptionMessage('** stands for whole segments');

        new Pattern('App\Foo**');
    }

    public function test_the_rejection_names_the_offending_pattern(): void
    {
        $this->expectExceptionMessage('App\Domain[0-9]');

        new Pattern('App\Domain[0-9]');
    }
}
