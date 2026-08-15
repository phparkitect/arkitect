<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate\Constraint;

use Arkitect\Evaluate\Constraint\Constraint;
use Arkitect\Evaluate\Constraint\IsAbstract;
use Arkitect\Evaluate\Constraint\IsReadonly;
use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;
use Arkitect\Tests\ParsedClassFixture;
use PHPUnit\Framework\TestCase;

/**
 * The constraints that answer from a single modifier on the declaration.
 * They share a shape, so they share a test rather than a base class.
 */
final class DeclarationChecksTest extends TestCase
{
    /** @return iterable<string, array{Constraint, ParsedClass, ParsedClass, string}> */
    public static function checks(): iterable
    {
        yield 'abstract' => [
            new IsAbstract(),
            ParsedClassFixture::create('App\Foo', isAbstract: true),
            ParsedClassFixture::create('App\Foo', isAbstract: false),
            'is not abstract',
        ];

        yield 'readonly' => [
            new IsReadonly(),
            ParsedClassFixture::create('App\Foo', isReadonly: true),
            ParsedClassFixture::create('App\Foo', isReadonly: false),
            'is not readonly',
        ];
    }

    /** @dataProvider checks */
    public function test_a_class_carrying_the_modifier_produces_no_violations(
        Constraint $constraint,
        ParsedClass $satisfying,
    ): void {
        self::assertCount(0, $constraint->evaluate($satisfying, new ClassGraph()));
    }

    /** @dataProvider checks */
    public function test_a_class_without_the_modifier_produces_a_violation(
        Constraint $constraint,
        ParsedClass $satisfying,
        ParsedClass $violating,
        string $detail,
    ): void {
        $violations = $constraint->evaluate($violating, new ClassGraph());

        self::assertCount(1, $violations);
        self::assertSame($detail, iterator_to_array($violations)[0]->detail);
        self::assertSame($constraint::class, iterator_to_array($violations)[0]->constraint);
    }
}
