<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate\Constraint;

use Arkitect\Evaluate\Constraint\Depth;
use Arkitect\Evaluate\Constraint\Implement;
use Arkitect\Resolve\ParsedClassGraph;
use Arkitect\Tests\ParsedClassFixture;
use PHPUnit\Framework\TestCase;

final class ImplementTest extends TestCase
{
    public function test_a_declared_interface_satisfies_the_default_transitive_check(): void
    {
        $class = ParsedClassFixture::create('App\Service', implements: ['App\Contract']);

        $violations = (new Implement('App\Contract'))->evaluate($class, new ParsedClassGraph($class))->violations;

        self::assertCount(0, $violations);
    }

    /**
     * The reason transitive is the default: a class inheriting the interface
     * from its parent does implement it, and reporting that as a violation
     * would be a false positive on the most common shape there is.
     */
    public function test_an_interface_inherited_from_the_parent_satisfies_the_transitive_check(): void
    {
        $class = ParsedClassFixture::create('App\Service', extends: ['App\Base']);
        $graph = new ParsedClassGraph($class, ParsedClassFixture::create('App\Base', implements: ['App\Contract']));

        $violations = (new Implement('App\Contract'))->evaluate($class, $graph)->violations;

        self::assertCount(0, $violations);
    }

    public function test_a_class_without_the_interface_anywhere_produces_a_violation(): void
    {
        $class = ParsedClassFixture::create('App\Loner');

        $violations = (new Implement('App\Contract'))->evaluate($class, new ParsedClassGraph($class))->violations;

        self::assertCount(1, $violations);

        $violation = iterator_to_array($violations)[0];
        self::assertSame(Implement::class, $violation->constraint);
        self::assertSame('does not implement App\Contract', $violation->detail);
    }

    public function test_direct_depth_accepts_a_declared_interface(): void
    {
        $class = ParsedClassFixture::create('App\Service', implements: ['App\Contract']);

        $violations = (new Implement('App\Contract', Depth::Direct))->evaluate($class, new ParsedClassGraph($class))->violations;

        self::assertCount(0, $violations);
    }

    public function test_direct_depth_rejects_an_interface_inherited_from_the_parent(): void
    {
        $class = ParsedClassFixture::create('App\Service', extends: ['App\Base']);
        $graph = new ParsedClassGraph($class, ParsedClassFixture::create('App\Base', implements: ['App\Contract']));

        $violations = (new Implement('App\Contract', Depth::Direct))->evaluate($class, $graph)->violations;

        self::assertCount(1, $violations);
        self::assertSame('does not directly implement App\Contract', iterator_to_array($violations)[0]->detail);
    }

    /**
     * A direct check reads the declaration and nothing else, so it always
     * has a definitive answer — the graph can't make it Unknown.
     */
    public function test_direct_depth_needs_no_graph_to_answer(): void
    {
        $class = ParsedClassFixture::create('App\Service', implements: ['App\Contract']);

        $violations = (new Implement('App\Contract', Depth::Direct))->evaluate($class, new ParsedClassGraph())->violations;

        self::assertCount(0, $violations);
    }

    public function test_an_unresolvable_chain_goes_to_the_unresolved_channel(): void
    {
        $class = ParsedClassFixture::create('App\Child', extends: ['Vendor\NeverParsed']);

        $outcome = (new Implement('App\Contract'))->evaluate($class, new ParsedClassGraph($class));

        self::assertCount(0, $outcome->violations);
        self::assertCount(1, $outcome->unresolved);
        self::assertSame(
            'cannot be checked against App\Contract: some ancestors were never parsed',
            iterator_to_array($outcome->unresolved)[0]->detail
        );
    }
}
