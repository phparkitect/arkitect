<?php

declare(strict_types=1);

namespace Arkitect\Tests;

use Arkitect\Baseline;
use Arkitect\Evaluate\Constraint\IsA;
use Arkitect\Evaluate\Constraint\IsFinal;
use Arkitect\Evaluate\Constraint\IsReadonly;
use Arkitect\Evaluate\Violation;
use Arkitect\Evaluate\Violations;
use PHPUnit\Framework\TestCase;

final class BaselineTest extends TestCase
{
    public function test_a_known_violation_is_recognised(): void
    {
        $baseline = Baseline::of(new Violations($this->violation('App\Order')));

        self::assertTrue($baseline->contains($this->violation('App\Order')));
    }

    public function test_another_class_or_another_rule_is_not_known(): void
    {
        $baseline = Baseline::of(new Violations($this->violation('App\Order')));

        self::assertFalse($baseline->contains($this->violation('App\Invoice')));
        self::assertFalse($baseline->contains($this->violation('App\Order', constraint: IsReadonly::class)));
    }

    /**
     * An unrelated edit above the class shifts every line below it. v1 needed
     * an opt-in setting to stop that invalidating the file; here a line
     * number is never part of what identifies a violation, so there is
     * nothing to opt into.
     */
    public function test_moving_the_code_down_the_file_changes_nothing(): void
    {
        $baseline = Baseline::of(new Violations($this->violation('App\Order', line: 12)));

        self::assertTrue($baseline->contains($this->violation('App\Order', line: 87)));
    }

    /**
     * The trap v1 has in another costume: it keys on the rendered error
     * string. `detail` is prose we write and may reword, so identity is
     * built from `key` instead — otherwise our own copy edit would
     * invalidate every baseline in the wild.
     */
    public function test_rewording_the_message_does_not_invalidate_it(): void
    {
        $baseline = Baseline::of(new Violations($this->violation('App\Order', detail: 'is not final')));

        self::assertTrue($baseline->contains($this->violation('App\Order', detail: 'must be declared final')));
    }

    public function test_two_dependencies_of_one_class_stay_distinct(): void
    {
        $baseline = Baseline::of(new Violations(
            $this->violation('App\Order', key: 'App\Infra\Db'),
        ));

        self::assertTrue($baseline->contains($this->violation('App\Order', key: 'App\Infra\Db')));
        self::assertFalse($baseline->contains($this->violation('App\Order', key: 'App\Http\Request')));
    }

    public function test_it_survives_a_round_trip_through_json(): void
    {
        $baseline = Baseline::of(new Violations(
            $this->violation('App\Order'),
            $this->violation('App\Invoice', key: 'App\Infra\Db'),
        ));

        $reloaded = Baseline::fromJson($baseline->toJson());

        self::assertTrue($reloaded->contains($this->violation('App\Order')));
        self::assertTrue($reloaded->contains($this->violation('App\Invoice', key: 'App\Infra\Db')));
        self::assertCount(2, $reloaded);
    }

    /**
     * The file is committed and reviewed, so a diff in it should mean
     * something changed.
     */
    public function test_the_file_is_written_in_a_stable_order(): void
    {
        $one = Baseline::of(new Violations($this->violation('App\Zebra'), $this->violation('App\Apple')));
        $other = Baseline::of(new Violations($this->violation('App\Apple'), $this->violation('App\Zebra')));

        self::assertSame($one->toJson(), $other->toJson());
    }

    /**
     * Everything stored is compared. v1 wrote line numbers it then had to be
     * told to ignore, which is a file claiming to mean more than it does.
     */
    public function test_it_stores_exactly_what_it_compares(): void
    {
        $json = Baseline::of(new Violations($this->violation('App\Order', line: 12)))->toJson();

        self::assertStringNotContainsString('12', $json);
        self::assertStringNotContainsString('src/Order.php', $json);
        self::assertStringNotContainsString('is not final', $json);
    }

    public function test_an_empty_baseline_recognises_nothing(): void
    {
        self::assertFalse(Baseline::empty()->contains($this->violation('App\Order')));
        self::assertCount(0, Baseline::empty());
    }

    /**
     * Two rules of the same kind on one class: without the key they would
     * collapse into a single entry, and baselining one would silently accept
     * the other.
     */
    public function test_two_targets_of_the_same_constraint_stay_distinct(): void
    {
        $baseline = Baseline::of(new Violations(
            $this->violation('App\Order', constraint: IsA::class, key: 'App\Contract'),
        ));

        self::assertTrue($baseline->contains(
            $this->violation('App\Order', constraint: IsA::class, key: 'App\Contract')
        ));
        self::assertFalse($baseline->contains(
            $this->violation('App\Order', constraint: IsA::class, key: 'App\OtherContract')
        ));
    }

    private function violation(
        string $fqcn,
        string $constraint = IsFinal::class,
        string $detail = 'is not final',
        ?string $key = null,
        int $line = 12,
    ): Violation {
        return Violation::create(
            ParsedClassFixture::create($fqcn, line: $line, filePath: 'src/Order.php'),
            $constraint,
            $detail,
            $key
        );
    }
}
