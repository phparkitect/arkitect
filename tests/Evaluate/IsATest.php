<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate;

use Arkitect\Evaluate\IsA;
use Arkitect\Parser\ClassKind;
use Arkitect\Parser\ParsedClass;
use Arkitect\Parser\TypeReference;
use Arkitect\Parser\TypeReferences;
use Arkitect\Resolve\ClassGraph;
use PHPUnit\Framework\TestCase;

final class IsATest extends TestCase
{
    public function test_a_class_that_extends_the_target_produces_no_violations(): void
    {
        $child = $this->classOf('App\Child', extends: ['App\Base']);
        $classGraph = new ClassGraph($child, $this->classOf('App\Base'));

        $violations = (new IsA('App\Base'))->evaluate($child, $classGraph);

        self::assertCount(0, $violations);
    }

    public function test_a_class_that_reaches_the_target_transitively_produces_no_violations(): void
    {
        $child = $this->classOf('App\Child', extends: ['App\Middle']);
        $classGraph = new ClassGraph(
            $child,
            $this->classOf('App\Middle', implements: ['App\Contract']),
            $this->classOf('App\Contract'),
        );

        $violations = (new IsA('App\Contract'))->evaluate($child, $classGraph);

        self::assertCount(0, $violations);
    }

    public function test_a_class_unrelated_to_the_target_produces_a_violation(): void
    {
        $class = $this->classOf('App\Loner');
        $classGraph = new ClassGraph($class, $this->classOf('App\Base'));

        $violations = (new IsA('App\Base'))->evaluate($class, $classGraph);

        self::assertCount(1, $violations);

        $violation = iterator_to_array($violations)[0];
        self::assertSame('App\Loner', $violation->fqcn);
        self::assertSame(IsA::class, $violation->expression);
        self::assertSame('is not a App\Base', $violation->detail);
    }

    /**
     * The ancestor exists but was never parsed, so the chain can't be walked
     * to an answer. Passing silently would hide an incomplete parse scope
     * (a missing vendor/, usually) behind a green run — see ARCHITECTURE.md,
     * Open: unknown ancestors are explicit rather than silently false.
     */
    public function test_an_unresolvable_ancestor_chain_is_reported_not_silently_passed(): void
    {
        $class = $this->classOf('App\Child', extends: ['Vendor\NeverParsed']);
        $classGraph = new ClassGraph($class);

        $violations = (new IsA('App\Base'))->evaluate($class, $classGraph);

        self::assertCount(1, $violations);

        $violation = iterator_to_array($violations)[0];
        self::assertSame('cannot be resolved against App\Base: some ancestors were never parsed', $violation->detail);
    }

    /**
     * @param list<string> $extends
     * @param list<string> $implements
     */
    private function classOf(string $fqcn, array $extends = [], array $implements = []): ParsedClass
    {
        return new ParsedClass(
            fqcn: $fqcn,
            line: 7,
            filePath: 'src/Child.php',
            kind: ClassKind::RegularClass,
            extends: new TypeReferences(...array_map(static fn ($n) => new TypeReference($n, 7), $extends)),
            implements: new TypeReferences(...array_map(static fn ($n) => new TypeReference($n, 7), $implements)),
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
