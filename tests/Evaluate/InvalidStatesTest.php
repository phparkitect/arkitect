<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate;

use Arkitect\Evaluate\Constraint\IsFinal;
use Arkitect\Evaluate\NotApplicableClasses;
use Arkitect\Evaluate\Rule;
use Arkitect\Evaluate\UnresolvedClasses;
use Arkitect\Evaluate\Violation;
use Arkitect\Evaluate\Violations;
use Arkitect\Parser\ClassKind;
use Arkitect\Parser\ParsedClass;
use Arkitect\Parser\TypeReferences;
use Arkitect\Tests\ParsedClassFixture;
use PHPUnit\Framework\TestCase;

/**
 * Every case here used to be constructible. They are collected in one place
 * because they share a cause: a docblock describing an invariant that
 * nothing enforced.
 */
final class InvalidStatesTest extends TestCase
{
    /**
     * The DSL already made because() impossible to skip. That guaranteed the
     * method was called, not that it said anything — and a report reading
     * "because " helps nobody.
     */
    public function test_a_rule_cannot_be_given_an_empty_reason(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Rule::allClasses()->should(new IsFinal())->because('');
    }

    public function test_a_reason_of_only_whitespace_is_no_reason(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Rule::allClasses()->should(new IsFinal())->because('   ');
    }

    /**
     * php-parser answers -1 when a node has no position. TypeReference
     * already refused it; this is the other way the same value reached a
     * report, since it is the fallback line for structural violations.
     */
    public function test_a_class_cannot_be_declared_at_a_line_that_does_not_exist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->classAt(-1);
    }

    public function test_a_class_cannot_come_from_nowhere(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ParsedClass(
            fqcn: 'App\Order',
            line: 1,
            filePath: '',
            kind: ClassKind::RegularClass,
            extends: new TypeReferences(),
            implements: new TypeReferences(),
            traits: new TypeReferences(),
            dependencies: new TypeReferences(),
            attributes: new TypeReferences(),
            docBlocks: [],
            isFinal: false,
            isReadonly: false,
            isAbstract: false,
        );
    }

    /**
     * The report classes take their location from an already-valid
     * ParsedClass or TypeReference, which is what a private constructor is
     * for: the invalid state isn't rejected, it cannot be built.
     */
    public function test_a_violation_cannot_be_built_from_arbitrary_values(): void
    {
        self::assertFalse((new \ReflectionClass(Violation::class))->getConstructor()?->isPublic());
    }

    public function test_a_violation_has_to_say_what_was_wrong(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Violation::create(ParsedClassFixture::create('App\Order'), IsFinal::class, '  ');
    }

    public function test_a_violation_names_a_constraint_not_an_arbitrary_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Violation::create(ParsedClassFixture::create('App\Order'), 'not a class name', 'is not final');
    }

    /**
     * `list<Violation>` in a docblock is a promise to the analyser. These are
     * typed variadics, so PHP itself keeps it — no hand-written check to get
     * out of step with the docblock. #599's array-not-splat rule is about the
     * classes users write in a config, not about collections like this one.
     */
    public function test_a_collection_of_violations_holds_only_violations(): void
    {
        $this->expectException(\TypeError::class);

        new Violations('not a violation'); // @phpstan-ignore-line
    }

    public function test_a_collection_of_unresolved_classes_holds_only_those(): void
    {
        $this->expectException(\TypeError::class);

        new UnresolvedClasses(42); // @phpstan-ignore-line
    }

    public function test_a_collection_of_not_applicable_classes_holds_only_those(): void
    {
        $this->expectException(\TypeError::class);

        new NotApplicableClasses(new \stdClass()); // @phpstan-ignore-line
    }

    public function test_a_rule_holds_only_selectors(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Rule(['App\Domain'], new IsFinal(), 'reasons');
    }

    private function classAt(int $line): ParsedClass
    {
        return new ParsedClass(
            fqcn: 'App\Order',
            line: $line,
            filePath: 'src/Order.php',
            kind: ClassKind::RegularClass,
            extends: new TypeReferences(),
            implements: new TypeReferences(),
            traits: new TypeReferences(),
            dependencies: new TypeReferences(),
            attributes: new TypeReferences(),
            docBlocks: [],
            isFinal: false,
            isReadonly: false,
            isAbstract: false,
        );
    }
}
