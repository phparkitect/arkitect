<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate;

use Arkitect\Evaluate\IsFinal;
use Arkitect\Parser\ClassKind;
use Arkitect\Parser\ParsedClass;
use Arkitect\Parser\TypeReferences;
use Arkitect\Resolve\ClassGraph;
use PHPUnit\Framework\TestCase;

final class IsFinalTest extends TestCase
{
    public function test_a_final_class_produces_no_violations(): void
    {
        $violations = (new IsFinal())->evaluate($this->classOf('App\Foo', isFinal: true), new ClassGraph());

        self::assertCount(0, $violations);
    }

    public function test_a_non_final_class_produces_one_violation(): void
    {
        $violations = (new IsFinal())->evaluate($this->classOf('App\Foo', isFinal: false), new ClassGraph());

        self::assertCount(1, $violations);
    }

    /**
     * Nothing in IsFinal refers to a specific node, so the violation falls
     * back to the class's own declaration line — the guarantee that every
     * violation carries a usable file:line (see ARCHITECTURE.md, stage 3).
     */
    public function test_the_violation_points_at_the_class_declaration(): void
    {
        $violations = (new IsFinal())->evaluate($this->classOf('App\Foo', isFinal: false), new ClassGraph());

        $violation = iterator_to_array($violations)[0];

        self::assertSame('App\Foo', $violation->fqcn);
        self::assertSame('src/Foo.php', $violation->filePath);
        self::assertSame(12, $violation->line);
    }

    public function test_the_violation_names_the_expression_that_produced_it(): void
    {
        $violations = (new IsFinal())->evaluate($this->classOf('App\Foo', isFinal: false), new ClassGraph());

        $violation = iterator_to_array($violations)[0];

        self::assertSame(IsFinal::class, $violation->expression);
        self::assertSame('is not final', $violation->detail);
    }

    private function classOf(string $fqcn, bool $isFinal): ParsedClass
    {
        return new ParsedClass(
            fqcn: $fqcn,
            line: 12,
            filePath: 'src/Foo.php',
            kind: ClassKind::RegularClass,
            extends: new TypeReferences(),
            implements: new TypeReferences(),
            traits: new TypeReferences(),
            dependencies: new TypeReferences(),
            attributes: new TypeReferences(),
            docBlocks: [],
            isFinal: $isFinal,
            isReadonly: false,
            isAbstract: false,
        );
    }
}
