<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate\Constraint;

use Arkitect\Evaluate\Constraint\DependOnlyOnTheseNamespaces;
use Arkitect\Resolve\ParsedClassGraph;
use Arkitect\Tests\ParsedClassFixture;
use PHPUnit\Framework\TestCase;

final class DependOnlyOnTheseNamespacesTest extends TestCase
{
    public function test_a_dependency_in_an_allowed_namespace_produces_no_violations(): void
    {
        $class = ParsedClassFixture::create('App\Domain\Order', dependencies: ['App\Shared\Money' => 4]);

        $violations = (new DependOnlyOnTheseNamespaces(['App\Shared']))->evaluate($class, new ParsedClassGraph())->violations;

        self::assertCount(0, $violations);
    }

    public function test_a_dependency_outside_the_allowed_namespaces_produces_a_violation(): void
    {
        $class = ParsedClassFixture::create('App\Domain\Order', dependencies: ['App\Infra\Db' => 4]);

        $violations = (new DependOnlyOnTheseNamespaces(['App\Shared']))->evaluate($class, new ParsedClassGraph())->violations;

        self::assertCount(1, $violations);

        $violation = iterator_to_array($violations)[0];
        self::assertSame(DependOnlyOnTheseNamespaces::class, $violation->constraint);
        self::assertSame('depends on App\Infra\Db', $violation->detail);
    }

    /**
     * The first constraint whose violations point somewhere other than the
     * class declaration: each bad dependency is reported on the line it is
     * actually referenced on.
     */
    public function test_each_violation_points_at_the_line_of_its_own_dependency(): void
    {
        $class = ParsedClassFixture::create(
            'App\Domain\Order',
            dependencies: ['App\Infra\Db' => 4, 'App\Http\Request' => 19],
            line: 3,
        );

        $violations = (new DependOnlyOnTheseNamespaces(['App\Shared']))->evaluate($class, new ParsedClassGraph())->violations;

        self::assertCount(2, $violations);
        self::assertSame([4, 19], array_map(static fn ($v) => $v->line, iterator_to_array($violations)));
    }

    public function test_the_class_own_namespace_is_always_allowed(): void
    {
        $class = ParsedClassFixture::create('App\Domain\Order', dependencies: ['App\Domain\Money' => 4]);

        $violations = (new DependOnlyOnTheseNamespaces(['App\Shared']))->evaluate($class, new ParsedClassGraph())->violations;

        self::assertCount(0, $violations);
    }

    /**
     * v1 allowed a dependency whenever the class sat anywhere beneath the
     * dependency's namespace, which quietly permitted every parent
     * namespace as well. Only the class's own namespace is implicit here.
     */
    public function test_a_parent_namespace_is_not_implicitly_allowed(): void
    {
        $class = ParsedClassFixture::create('App\Domain\Order', dependencies: ['App\Kernel' => 4]);

        $violations = (new DependOnlyOnTheseNamespaces(['App\Shared']))->evaluate($class, new ParsedClassGraph())->violations;

        self::assertCount(1, $violations);
    }

    /**
     * Otherwise every rule would have to whitelist the entire standard
     * library before it could be used at all.
     */
    public function test_internal_classes_are_never_violations(): void
    {
        $class = ParsedClassFixture::create(
            'App\Domain\Order',
            dependencies: ['DateTimeImmutable' => 4, 'InvalidArgumentException' => 9],
        );

        $violations = (new DependOnlyOnTheseNamespaces(['App\Shared']))->evaluate($class, new ParsedClassGraph())->violations;

        self::assertCount(0, $violations);
    }

    /**
     * A class that happens to be loaded because arkitect itself is running
     * is not internal, and must not get a free pass.
     */
    public function test_a_loaded_user_defined_class_is_not_treated_as_internal(): void
    {
        $class = ParsedClassFixture::create(
            'App\Domain\Order',
            dependencies: ['PHPUnit\Framework\TestCase' => 4],
        );

        $violations = (new DependOnlyOnTheseNamespaces(['App\Shared']))->evaluate($class, new ParsedClassGraph())->violations;

        self::assertCount(1, $violations);
    }

    public function test_several_allowed_namespaces_are_all_honoured(): void
    {
        $class = ParsedClassFixture::create(
            'App\Domain\Order',
            dependencies: ['App\Shared\Money' => 4, 'App\Events\Bus' => 5],
        );

        $violations = (new DependOnlyOnTheseNamespaces(['App\Shared', 'App\Events']))->evaluate($class, new ParsedClassGraph())->violations;

        self::assertCount(0, $violations);
    }

    public function test_a_class_in_the_global_namespace_has_no_implicit_allowance(): void
    {
        $class = ParsedClassFixture::create('Order', dependencies: ['App\Infra\Db' => 4]);

        $violations = (new DependOnlyOnTheseNamespaces(['App\Shared']))->evaluate($class, new ParsedClassGraph())->violations;

        self::assertCount(1, $violations);
    }
}
