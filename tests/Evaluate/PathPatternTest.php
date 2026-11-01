<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate;

use Arkitect\Evaluate\PathPattern;
use PHPUnit\Framework\TestCase;

final class PathPatternTest extends TestCase
{
    public function test_a_directory_contains_the_files_in_it_and_beneath_it(): void
    {
        $pattern = new PathPattern('src/Domain');

        self::assertTrue($pattern->contains('src/Domain/Order.php'));
        self::assertTrue($pattern->contains('src/Domain/Deeply/Nested/Thing.php'));
    }

    public function test_a_file_elsewhere_is_not_contained(): void
    {
        self::assertFalse((new PathPattern('src/Domain'))->contains('src/Infra/Connection.php'));
    }

    public function test_a_sibling_directory_sharing_the_prefix_is_not_contained(): void
    {
        self::assertFalse((new PathPattern('src/Domain'))->contains('src/DomainEvents/OrderPlaced.php'));
    }

    public function test_a_file_at_the_root_is_in_no_directory(): void
    {
        self::assertFalse((new PathPattern('src'))->contains('run.php'));
    }

    public function test_the_directory_written_is_not_one_of_the_files_it_contains(): void
    {
        self::assertFalse((new PathPattern('src/Domain/Order.php'))->contains('src/Domain/Order.php'));
    }

    public function test_a_star_stays_inside_one_segment(): void
    {
        $pattern = new PathPattern('packages/*/src');

        self::assertTrue($pattern->contains('packages/billing/src/Invoice.php'));
        self::assertFalse($pattern->contains('packages/billing/legacy/src/Invoice.php'));
    }

    public function test_a_double_star_stands_for_any_number_of_segments_including_none(): void
    {
        $pattern = new PathPattern('src/**/Domain');

        self::assertTrue($pattern->contains('src/Domain/Order.php'));
        self::assertTrue($pattern->contains('src/Billing/Domain/Invoice.php'));
        self::assertTrue($pattern->contains('src/Billing/Nested/Domain/Invoice.php'));
    }

    public function test_a_star_can_stand_for_part_of_a_segment(): void
    {
        $pattern = new PathPattern('src/*Legacy');

        self::assertTrue($pattern->contains('src/OrderLegacy/Thing.php'));
        self::assertFalse($pattern->contains('src/Order/Thing.php'));
    }

    public function test_a_separator_at_either_end_is_the_same_pattern(): void
    {
        self::assertTrue((new PathPattern('/src/Domain/'))->contains('src/Domain/Order.php'));
    }

    public function test_dots_and_dashes_are_ordinary_characters_in_a_path(): void
    {
        self::assertTrue((new PathPattern('packages/my-pkg/src'))->contains('packages/my-pkg/src/Thing.php'));
    }

    public function test_a_regex_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PathPattern('src/(Domain|Infra)');
    }

    public function test_a_double_star_sharing_a_segment_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PathPattern('src/**Domain');
    }

    public function test_a_relative_step_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PathPattern('../src');
    }

    public function test_an_empty_pattern_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PathPattern('/');
    }
}
