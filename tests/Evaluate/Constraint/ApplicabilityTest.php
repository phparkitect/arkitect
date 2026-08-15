<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate\Constraint;

use Arkitect\Evaluate\Constraint\IsAbstract;
use Arkitect\Evaluate\Constraint\IsFinal;
use Arkitect\Evaluate\Constraint\IsReadonly;
use Arkitect\Parser\ClassKind;
use Arkitect\Resolve\ClassGraph;
use Arkitect\Tests\ParsedClassFixture;
use PHPUnit\Framework\TestCase;

/**
 * `final`, `readonly` and `abstract` are not choices an interface, trait or
 * enum declined to make — PHP rejects the keywords outright. Reporting one
 * as a violation asks the user for an edit the compiler would refuse, so
 * these constraints say the requirement cannot apply instead.
 */
final class ApplicabilityTest extends TestCase
{
    /** @return iterable<string, array{ClassKind, string}> */
    public static function kindsThatCannotBeFinal(): iterable
    {
        yield 'interface' => [ClassKind::Interface, 'an interface cannot be final'];
        yield 'trait' => [ClassKind::Trait, 'a trait cannot be final'];
    }

    /** @dataProvider kindsThatCannotBeFinal */
    public function test_is_final_does_not_apply_to_kinds_that_cannot_carry_it(
        ClassKind $kind,
        string $reason,
    ): void {
        $class = ParsedClassFixture::create('App\Thing', kind: $kind);

        $outcome = (new IsFinal())->evaluate($class, new ClassGraph());

        self::assertCount(0, $outcome->violations);
        self::assertCount(1, $outcome->notApplicable);
        self::assertSame($reason, iterator_to_array($outcome->notApplicable)[0]->detail);
    }

    /**
     * Applicability is not decided by kind alone: an abstract class is an
     * ordinary class, and still cannot be final.
     */
    public function test_is_final_does_not_apply_to_an_abstract_class(): void
    {
        $class = ParsedClassFixture::create('App\Base', isAbstract: true);

        $outcome = (new IsFinal())->evaluate($class, new ClassGraph());

        self::assertCount(0, $outcome->violations);
        self::assertSame(
            'an abstract class cannot be final',
            iterator_to_array($outcome->notApplicable)[0]->detail
        );
    }

    /**
     * An enum is final, it just can't say so. Answering "satisfied" is
     * truer than "not applicable": the rule wanted a type nothing can
     * extend, and that is exactly what an enum is.
     */
    public function test_is_final_is_satisfied_by_an_enum(): void
    {
        $class = ParsedClassFixture::create('App\Status', kind: ClassKind::Enum, isFinal: true);

        $outcome = (new IsFinal())->evaluate($class, new ClassGraph());

        self::assertCount(0, $outcome->violations);
        self::assertCount(0, $outcome->notApplicable);
    }

    public function test_is_abstract_does_not_apply_to_a_final_class(): void
    {
        $class = ParsedClassFixture::create('App\Thing', isFinal: true);

        $outcome = (new IsAbstract())->evaluate($class, new ClassGraph());

        self::assertCount(0, $outcome->violations);
        self::assertSame(
            'a final class cannot be abstract',
            iterator_to_array($outcome->notApplicable)[0]->detail
        );
    }

    public function test_is_abstract_does_not_apply_to_an_interface(): void
    {
        $class = ParsedClassFixture::create('App\Repo', kind: ClassKind::Interface);

        $outcome = (new IsAbstract())->evaluate($class, new ClassGraph());

        self::assertCount(1, $outcome->notApplicable);
        self::assertSame(
            'an interface is already abstract',
            iterator_to_array($outcome->notApplicable)[0]->detail
        );
    }

    public function test_is_readonly_does_not_apply_to_an_enum(): void
    {
        $class = ParsedClassFixture::create('App\Status', kind: ClassKind::Enum);

        $outcome = (new IsReadonly())->evaluate($class, new ClassGraph());

        self::assertCount(0, $outcome->violations);
        self::assertCount(1, $outcome->notApplicable);
    }

    public function test_an_ordinary_class_is_still_judged(): void
    {
        $class = ParsedClassFixture::create('App\Order', isFinal: false);

        $outcome = (new IsFinal())->evaluate($class, new ClassGraph());

        self::assertCount(1, $outcome->violations);
        self::assertCount(0, $outcome->notApplicable);
    }
}
