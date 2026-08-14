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

    public function test_a_pattern_without_wildcards_matches_everything_beneath_it(): void
    {
        $pattern = new Pattern('App\Domain');

        self::assertTrue($pattern->matches('App\Domain\Order'));
        self::assertTrue($pattern->matches('App\Domain\Deeply\Nested\Thing'));
    }

    /**
     * The separator matters: without it, a namespace pattern would swallow
     * every sibling namespace that merely starts with the same letters.
     */
    public function test_a_namespace_does_not_match_a_sibling_sharing_its_prefix(): void
    {
        self::assertFalse((new Pattern('App\Domain'))->matches('App\DomainEvents\Something'));
    }

    public function test_a_trailing_separator_is_accepted_and_means_the_same(): void
    {
        self::assertTrue((new Pattern('App\Domain\\'))->matches('App\Domain\Order'));
    }

    public function test_a_star_matches_any_run_of_characters_including_separators(): void
    {
        $pattern = new Pattern('App\*\Order');

        self::assertTrue($pattern->matches('App\Domain\Order'));
        self::assertTrue($pattern->matches('App\Domain\Nested\Order'));
    }

    /**
     * A wildcard pattern keeps the "anything beneath it" half too, so the
     * meaning of a pattern doesn't quietly change with the presence of a *.
     */
    public function test_a_wildcard_pattern_also_matches_everything_beneath_it(): void
    {
        $pattern = new Pattern('App\*\Domain');

        self::assertTrue($pattern->matches('App\Modules\Billing\Domain\Invoice'));
        self::assertTrue($pattern->matches('App\Modules\Billing\Domain'));
        self::assertFalse($pattern->matches('App\Modules\Billing\Infra\Db'));
    }

    public function test_a_suffix_pattern_matches_by_name(): void
    {
        $pattern = new Pattern('*Controller');

        self::assertTrue($pattern->matches('App\Http\UserController'));
        self::assertFalse($pattern->matches('App\Http\UserRepository'));
    }

    public function test_a_question_mark_matches_exactly_one_character(): void
    {
        $pattern = new Pattern('App\V?\Thing');

        self::assertTrue($pattern->matches('App\V1\Thing'));
        self::assertFalse($pattern->matches('App\V10\Thing'));
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

    /**
     * Rejected at construction rather than deep inside a run: the only
     * wildcards are * and ?, so a regex is a mistake worth naming early.
     */
    public function test_a_regex_is_rejected_as_a_pattern(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Pattern('/^App\\\\.*/');
    }

    public function test_the_rejection_names_the_offending_pattern(): void
    {
        $this->expectExceptionMessage('App\Domain[0-9]');

        new Pattern('App\Domain[0-9]');
    }
}
