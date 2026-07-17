<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\CLI;

use Arkitect\CLI\Baseline;
use Arkitect\Rules\Violation;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class BaselineTest extends TestCase
{
    public function test_apply_to_removes_baseline_violations_and_counts_stale_entries(): void
    {
        $stillPresent = new Violation('App\Controller\Shop', 'should have name end with Controller', 10);
        $alreadyFixed = new Violation('App\Controller\Fixed', 'should have name end with Controller', 3);

        $baselineViolations = new Violations();
        $baselineViolations->add($stillPresent);
        $baselineViolations->add($alreadyFixed);

        $baseline = Baseline::fromViolations($baselineViolations);

        $current = new Violations();
        $current->add($stillPresent);

        $baseline->applyTo($current, false);

        self::assertCount(0, $current);
        self::assertSame(1, $baseline->getStaleViolationsCount());
    }

    public function test_prune_keeps_only_entries_matching_a_current_violation(): void
    {
        $stillPresent = new Violation('App\Controller\Shop', 'should have name end with Controller', 10);
        $alreadyFixed = new Violation('App\Controller\Fixed', 'should have name end with Controller', 3);

        $baselineViolations = new Violations();
        $baselineViolations->add($stillPresent);
        $baselineViolations->add($alreadyFixed);

        $current = new Violations();
        $current->add($stillPresent);

        $pruned = Baseline::fromViolations($baselineViolations)->prune($current);

        self::assertCount(1, $pruned->getViolations());
        self::assertEquals('App\Controller\Shop', $pruned->getViolations()->get(0)->getFqcn());
    }

    public function test_prune_never_adds_new_violations(): void
    {
        $newViolation = new Violation('App\Controller\NewViolation', 'should implement ContainerInterface', 5);

        $current = new Violations();
        $current->add($newViolation);

        $pruned = Baseline::empty()->prune($current);

        self::assertCount(0, $pruned->getViolations());
    }

    public function test_prune_refreshes_stale_line_numbers(): void
    {
        $baselineViolations = new Violations();
        $baselineViolations->add(new Violation('App\Controller\Shop', 'should have name end with Controller', 10));

        $current = new Violations();
        $current->add(new Violation('App\Controller\Shop', 'should have name end with Controller', 42));

        $pruned = Baseline::fromViolations($baselineViolations)->prune($current);

        self::assertCount(1, $pruned->getViolations());
        self::assertEquals(42, $pruned->getViolations()->get(0)->getLine());
    }

    public function test_without_line_numbers_returns_a_copy_with_stripped_line_numbers(): void
    {
        $baselineViolations = new Violations();
        $baselineViolations->add(new Violation('App\Controller\Shop', 'should have name end with Controller', 10));

        $stripped = Baseline::fromViolations($baselineViolations)->withoutLineNumbers();

        self::assertNull($stripped->getViolations()->get(0)->getLine());
        self::assertEquals(10, $baselineViolations->get(0)->getLine());
    }
}
