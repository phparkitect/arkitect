<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate\Constraint;

use Arkitect\Evaluate\Constraint\IsFinal;
use Arkitect\Resolve\ClassGraph;
use Arkitect\Tests\ParsedClassFixture;
use PHPUnit\Framework\TestCase;

final class IsFinalTest extends TestCase
{
    public function test_a_final_class_produces_no_violations(): void
    {
        $violations = (new IsFinal())->evaluate(ParsedClassFixture::create('App\Foo', isFinal: true, line: 12), new ClassGraph());

        self::assertCount(0, $violations);
    }

    public function test_a_non_final_class_produces_one_violation(): void
    {
        $violations = (new IsFinal())->evaluate(ParsedClassFixture::create('App\Foo', isFinal: false, line: 12), new ClassGraph());

        self::assertCount(1, $violations);
    }

    /**
     * Nothing in IsFinal refers to a specific node, so the violation falls
     * back to the class's own declaration line — the guarantee that every
     * violation carries a usable file:line (see ARCHITECTURE.md, stage 3).
     */
    public function test_the_violation_points_at_the_class_declaration(): void
    {
        $violations = (new IsFinal())->evaluate(ParsedClassFixture::create('App\Foo', isFinal: false, line: 12), new ClassGraph());

        $violation = iterator_to_array($violations)[0];

        self::assertSame('App\Foo', $violation->fqcn);
        self::assertSame('src/Foo.php', $violation->filePath);
        self::assertSame(12, $violation->line);
    }

    public function test_the_violation_names_the_constraint_that_produced_it(): void
    {
        $violations = (new IsFinal())->evaluate(ParsedClassFixture::create('App\Foo', isFinal: false, line: 12), new ClassGraph());

        $violation = iterator_to_array($violations)[0];

        self::assertSame(IsFinal::class, $violation->constraint);
        self::assertSame('is not final', $violation->detail);
    }
}
