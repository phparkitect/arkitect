<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Rules;

use Arkitect\Exceptions\IndexNotFoundException;
use Arkitect\Rules\Violation;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class ViolationsTest extends TestCase
{
    private Violations $violationStore;

    private Violation $violation;

    protected function setUp(): void
    {
        $this->violationStore = new Violations();
        $this->violation = new Violation(
            'App\Controller\ProductController',
            'should implement ContainerInterface'
        );
        $this->violationStore->add($this->violation);
    }

    public function test_add_elements_to_store_and_get_it(): void
    {
        self::assertEquals($this->violation, $this->violationStore->get(0));
    }

    public function test_add_elements_to_store_and_cant_get_it_if_index_not_valid(): void
    {
        $this->expectException(IndexNotFoundException::class);
        $this->expectExceptionMessage('Index not found 1111');
        self::assertEquals('', $this->violationStore->get(1111));
    }

    public function test_count(): void
    {
        $violation = new Violation(
            'App\Controller\Shop',
            'should have name end with Controller'
        );
        $this->violationStore->add($violation);
        self::assertEquals(2, $this->violationStore->count());
    }

    public function test_get_iterable(): void
    {
        $violation = new Violation(
            'App\Controller\Shop',
            'should have name end with Controller'
        );
        $this->violationStore->add($violation);
        $iterable = $this->violationStore->getIterator();

        self::assertEquals([
            $this->violation,
            $violation,
        ], iterator_to_array($iterable));
    }

    public function test_get_array(): void
    {
        $violation1 = new Violation(
            'App\Controller\Shop',
            'should have name end with Controller'
        );
        $violation2 = new Violation(
            'App\Controller\Shop',
            'should implement AbstractController'
        );
        $this->violationStore->add($violation1);
        $this->violationStore->add($violation2);

        self::assertEquals([
            $this->violation,
            $violation1,
            $violation2,
        ], $this->violationStore->toArray());
    }

    public function test_match_reports_the_violations_the_baseline_does_not_know(): void
    {
        $violation1 = new Violation(
            'App\Controller\Shop',
            'should have name end with Controller'
        );
        $this->violationStore->add($violation1);

        $violation2 = new Violation(
            'App\Controller\Shop',
            'should implement AbstractController'
        );
        $this->violationStore->add($violation2);

        self::assertCount(3, $this->violationStore->toArray());

        $violationsBaseline = new Violations();
        $violationsBaseline->add($this->violation);

        $new = $this->violationStore->matchAgainst($violationsBaseline)->new();

        self::assertCount(2, $new);
        self::assertEquals([
            $violation1,
            $violation2,
        ], $new->toArray());
    }

    public function test_sort(): void
    {
        $violationStore = new Violations();
        $violation1 = new Violation(
            'App\Controller\Shop',
            'AAA',
            20
        );
        $violation2 = new Violation(
            'App\Controller\Shop',
            'BBB',
            10
        );
        $violation3 = new Violation(
            'App\Controller\Shop',
            'AAA',
            10
        );
        $violation4 = new Violation(
            'App\Controller\Abc',
            'CCC',
            30
        );
        $violationStore->add($violation1);
        $violationStore->add($violation2);
        $violationStore->add($violation3);
        $violationStore->add($violation4);

        self::assertEquals([
            $violation1,
            $violation2,
            $violation3,
            $violation4,
        ], $violationStore->toArray());

        $violationStore->sort();

        self::assertSame([
            $violation4, // fqcn is most important
            $violation3, // then line number
            $violation2, // then error message
            $violation1,
        ], $violationStore->toArray());
    }

    public function test_match_pairs_when_rule_description_changes(): void
    {
        $violations = new Violations();
        $violations->add(new Violation(
            'App\Foo',
            'depends on App\Bar, but should depend only on classes in one of these namespaces: App\Domain, App\Shared',
            10
        ));

        $baseline = new Violations();
        $baseline->add(new Violation(
            'App\Foo',
            'depends on App\Bar, but should depend only on classes in one of these namespaces: App\Domain',
            10
        ));

        self::assertCount(0, $violations->matchAgainst($baseline)->new());
    }

    public function test_match_pairs_when_rule_description_changes_and_the_violation_moved(): void
    {
        $violations = new Violations();
        $violations->add(new Violation(
            'App\Foo',
            'depends on App\Bar, but should depend only on classes in one of these namespaces: App\Domain, App\Shared',
            15
        ));

        $baseline = new Violations();
        $baseline->add(new Violation(
            'App\Foo',
            'depends on App\Bar, but should depend only on classes in one of these namespaces: App\Domain',
            10
        ));

        self::assertCount(0, $violations->matchAgainst($baseline)->new());
    }

    public function test_match_reports_a_duplicate_the_baseline_only_knows_once(): void
    {
        $error = 'depends on App\Bar, but should depend only on classes in one of these namespaces: App\Domain';

        $baseline = new Violations();
        $baseline->add(new Violation('App\Foo', $error, 10, 'src/Foo.php'));

        $violations = new Violations();
        $violations->add(new Violation('App\Foo', $error, 10, 'src/Foo.php'));
        $violations->add(new Violation('App\Foo', $error, 10, 'src/Foo.php'));

        self::assertCount(1, $violations->matchAgainst($baseline)->new(), 'each baseline entry covers one violation, not every identical one');
    }

    public function test_match_reports_the_violation_that_was_really_added(): void
    {
        $error = 'depends on App\Bar, but should depend only on classes in one of these namespaces: App\Domain';

        $baseline = new Violations();
        $baseline->add(new Violation('App\Foo', $error, 10));
        $baseline->add(new Violation('App\Foo', $error, 20));
        $baseline->add(new Violation('App\Foo', $error, 30));

        $violations = new Violations();
        $violations->add(new Violation('App\Foo', $error, 10));
        $violations->add(new Violation('App\Foo', $error, 15));
        $violations->add(new Violation('App\Foo', $error, 20));
        $violations->add(new Violation('App\Foo', $error, 30));

        $new = $violations->matchAgainst($baseline)->new();

        self::assertCount(1, $new);
        self::assertSame(15, $new->get(0)->getLine(), 'the untouched violations pair by position, so what is left is the added one');
    }

    public function test_match_does_not_pair_different_dependency(): void
    {
        $violations = new Violations();
        $violations->add(new Violation(
            'App\Foo',
            'depends on App\Baz, but should depend only on classes in one of these namespaces: App\Domain',
            10
        ));

        $baseline = new Violations();
        $baseline->add(new Violation(
            'App\Foo',
            'depends on App\Bar, but should depend only on classes in one of these namespaces: App\Domain',
            10
        ));

        self::assertCount(1, $violations->matchAgainst($baseline)->new());
    }

    public function test_match_pairs_self_explanatory_messages(): void
    {
        $violations = new Violations();
        $violations->add(new Violation(
            'App\Foo',
            'should be final because we want immutability'
        ));

        $baseline = new Violations();
        $baseline->add(new Violation(
            'App\Foo',
            'should be final because we want immutability'
        ));

        self::assertCount(0, $violations->matchAgainst($baseline)->new());
    }

    public function test_match_pairs_self_explanatory_messages_when_because_is_reworded(): void
    {
        $violations = new Violations();
        $violations->add(new Violation(
            'App\Foo',
            'should be final because we want immutability and avoid side-effects'
        ));

        $baseline = new Violations();
        $baseline->add(new Violation(
            'App\Foo',
            'should be final because we want immutability'
        ));

        self::assertCount(0, $violations->matchAgainst($baseline)->new());
    }

    public function test_violation_without_line_number_returns_copy_with_null_line(): void
    {
        $violation = new Violation('App\Foo', 'some error', 42, '/src/Foo.php');
        $stripped = $violation->withoutLineNumber();

        self::assertNull($stripped->getLine());
        self::assertEquals('App\Foo', $stripped->getFqcn());
        self::assertEquals('some error', $stripped->getError());
        self::assertEquals('/src/Foo.php', $stripped->getFilePath());
    }

    public function test_without_line_numbers_strips_all_line_numbers(): void
    {
        $violations = new Violations();
        $violations->add(new Violation('App\Foo', 'some error', 42, '/src/Foo.php'));
        $violations->add(new Violation('App\Bar', 'other error', 10, '/src/Bar.php'));

        $stripped = $violations->withoutLineNumbers();

        self::assertCount(2, $stripped);
        self::assertNull($stripped->get(0)->getLine());
        self::assertNull($stripped->get(1)->getLine());
        self::assertEquals('App\Foo', $stripped->get(0)->getFqcn());
        self::assertEquals('App\Bar', $stripped->get(1)->getFqcn());
    }

    public function test_without_line_numbers_does_not_mutate_original(): void
    {
        $violations = new Violations();
        $violations->add(new Violation('App\Foo', 'some error', 42, '/src/Foo.php'));

        $violations->withoutLineNumbers();

        self::assertEquals(42, $violations->get(0)->getLine());
    }

    public function test_remove_violations_from_violations_ignore_linenumber(): void
    {
        $violation1 = new Violation(
            'App\Controller\Shop',
            'should have name end with Controller',
            42
        );
        $this->violationStore->add($violation1);

        $violation2 = new Violation(
            'App\Controller\Shop',
            'should implement AbstractController',
            21
        );
        $this->violationStore->add($violation2);

        $violation3 = new Violation(
            'App\Controller\Shop',
            'should have name end with Controller',
            5
        );
        $this->violationStore->add($violation3);

        self::assertCount(4, $this->violationStore->toArray());

        $violationsBaseline = new Violations();
        $violationsBaseline->add(new Violation(
            'App\Controller\Shop',
            'should have name end with Controller',
            21
        ));

        $new = $this->violationStore->matchAgainst($violationsBaseline)->new();

        self::assertCount(3, $new);
        self::assertEquals([
            $this->violation,
            $violation2,
            $violation3,
        ], $new->toArray());
    }

    public function test_match_stale_returns_zero_when_everything_still_occurs(): void
    {
        $baseline = new Violations();
        $baseline->add($this->violation);

        $current = new Violations();
        $current->add($this->violation);

        self::assertCount(0, $current->matchAgainst($baseline)->stale());
    }

    public function test_match_stale_counts_fixed_baseline_entries(): void
    {
        $stillPresent = new Violation('App\Controller\Shop', 'should have name end with Controller', 10);
        $fixed = new Violation('App\Controller\Shop', 'should implement AbstractController', 20);

        $baseline = new Violations();
        $baseline->add($stillPresent);
        $baseline->add($fixed);

        $current = new Violations();
        $current->add($stillPresent);

        self::assertCount(1, $current->matchAgainst($baseline)->stale());
    }

    public function test_match_does_not_call_stale_a_violation_that_only_moved(): void
    {
        $stillPresent = new Violation('App\Controller\Shop', 'should have name end with Controller', 10);
        $fixed = new Violation('App\Controller\Shop', 'should implement AbstractController', 20);
        $stillPresentMovedLine = new Violation('App\Controller\Shop', 'should have name end with Controller', 15);

        $baseline = new Violations();
        $baseline->add($stillPresent);
        $baseline->add($fixed);

        $current = new Violations();
        $current->add($stillPresentMovedLine);

        self::assertCount(1, $current->matchAgainst($baseline)->stale());
    }

    public function test_match_stale_does_not_mutate_either_set(): void
    {
        $stillPresent = new Violation('App\Controller\Shop', 'should have name end with Controller', 10);

        $baseline = new Violations();
        $baseline->add($stillPresent);

        $current = new Violations();
        $current->add($stillPresent);

        $current->matchAgainst($baseline);

        self::assertCount(1, $baseline);
        self::assertCount(1, $current);
    }

    public function test_match_known_keeps_only_matching_violations_with_this_sets_line_numbers(): void
    {
        $current = new Violations();
        $current->add(new Violation('App\Controller\Shop', 'should have name end with Controller', 25));
        $current->add(new Violation('App\Controller\NewViolation', 'should implement ContainerInterface', 5));

        $baseline = new Violations();
        $baseline->add(new Violation('App\Controller\Shop', 'should have name end with Controller', 10));
        $baseline->add(new Violation('App\Controller\Fixed', 'should have name end with Controller', 3));

        $intersection = $current->matchAgainst($baseline)->known();

        self::assertCount(1, $intersection);
        self::assertEquals('App\Controller\Shop', $intersection->get(0)->getFqcn());
        self::assertEquals(25, $intersection->get(0)->getLine());
    }

    public function test_match_known_matches_each_entry_at_most_once(): void
    {
        $duplicated = new Violation('App\Controller\Shop', 'should have name end with Controller', 10);

        $current = new Violations();
        $current->add($duplicated);
        $current->add($duplicated);

        $baseline = new Violations();
        $baseline->add($duplicated);

        self::assertCount(1, $current->matchAgainst($baseline)->known());
    }

    public function test_match_known_does_not_mutate_either_set(): void
    {
        $violation = new Violation('App\Controller\Shop', 'should have name end with Controller', 10);

        $current = new Violations();
        $current->add($violation);

        $baseline = new Violations();
        $baseline->add($violation);

        $current->matchAgainst($baseline);

        self::assertCount(1, $current);
        self::assertCount(1, $baseline);
    }
}
