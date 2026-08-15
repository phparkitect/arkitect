<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate;

use Arkitect\Evaluate\Constraint;
use Arkitect\Evaluate\Pattern;
use Arkitect\Evaluate\Rule;
use Arkitect\Evaluate\Selector;
use Arkitect\Resolve\ClassGraph;
use Arkitect\Tests\ParsedClassFixture;
use PHPUnit\Framework\TestCase;

/**
 * A rule written with `\App\Contract` used to fail silently and in the worst
 * possible direction: the name never matched anything stored, so IsA
 * reported a violation for every class in a codebase that satisfied it, and
 * ResideInNamespace selected nothing at all. Both are spellings of the same
 * name, so both now normalize.
 */
final class LeadingSeparatorTest extends TestCase
{
    public function test_a_constraint_target_written_with_a_leading_separator_still_matches(): void
    {
        $service = ParsedClassFixture::create('App\Service', implements: ['App\Contract']);
        $graph = new ClassGraph($service, ParsedClassFixture::create('App\Contract'));

        $result = Rule::allClasses()
            ->should(new Constraint\IsA('\App\Contract'))
            ->because('reasons')
            ->check([$service], $graph);

        self::assertCount(0, $result->violations);
    }

    public function test_a_selector_target_written_with_a_leading_separator_still_selects(): void
    {
        $service = ParsedClassFixture::create('App\Service', implements: ['App\Contract']);
        $graph = new ClassGraph($service, ParsedClassFixture::create('App\Contract'));

        self::assertSame(
            Selector\Selection::Yes,
            (new Selector\Implement('\App\Contract'))->matches($service, $graph)
        );
    }

    public function test_a_namespace_written_with_a_leading_separator_still_selects(): void
    {
        $class = ParsedClassFixture::create('App\Domain\Order');

        self::assertSame(
            Selector\Selection::Yes,
            (new Selector\ResideInNamespace('\App\Domain'))->matches($class, new ClassGraph())
        );
    }

    public function test_a_pattern_written_with_a_leading_separator_still_matches(): void
    {
        self::assertTrue((new Pattern('\App\Domain'))->matches('App\Domain\Order'));
    }

    public function test_a_dependency_namespace_written_with_a_leading_separator_still_matches(): void
    {
        $class = ParsedClassFixture::create('App\Domain\Order', dependencies: ['App\Infra\Db' => 4]);

        $violations = (new Constraint\NotDependOnTheseNamespaces(['\App\Infra']))
            ->evaluate($class, new ClassGraph())
            ->violations;

        self::assertCount(1, $violations);
    }
}
