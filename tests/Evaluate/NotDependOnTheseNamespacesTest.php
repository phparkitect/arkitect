<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate;

use Arkitect\Evaluate\NotDependOnTheseNamespaces;
use Arkitect\Resolve\ClassGraph;
use Arkitect\Tests\ParsedClassFixture;
use PHPUnit\Framework\TestCase;

final class NotDependOnTheseNamespacesTest extends TestCase
{
    public function test_a_dependency_on_a_forbidden_namespace_produces_a_violation(): void
    {
        $class = ParsedClassFixture::create('App\Domain\Order', dependencies: ['App\Infra\Db' => 12]);

        $violations = (new NotDependOnTheseNamespaces(['App\Infra']))->evaluate($class, new ClassGraph());

        self::assertCount(1, $violations);

        $violation = iterator_to_array($violations)[0];
        self::assertSame(NotDependOnTheseNamespaces::class, $violation->expression);
        self::assertSame('depends on App\Infra\Db', $violation->detail);
        self::assertSame(12, $violation->line);
    }

    public function test_a_dependency_elsewhere_produces_no_violations(): void
    {
        $class = ParsedClassFixture::create('App\Domain\Order', dependencies: ['App\Shared\Money' => 4]);

        $violations = (new NotDependOnTheseNamespaces(['App\Infra']))->evaluate($class, new ClassGraph());

        self::assertCount(0, $violations);
    }

    /**
     * Not the logical negation of DependOnlyOnTheseNamespaces, which is why
     * both exist: this one names what is forbidden and stays silent about
     * everything else, so nothing is implicitly allowed or disallowed.
     */
    public function test_the_class_own_namespace_gets_no_special_treatment(): void
    {
        $class = ParsedClassFixture::create('App\Infra\Repo', dependencies: ['App\Infra\Db' => 4]);

        $violations = (new NotDependOnTheseNamespaces(['App\Infra']))->evaluate($class, new ClassGraph());

        self::assertCount(1, $violations);
    }

    public function test_every_forbidden_namespace_is_checked(): void
    {
        $class = ParsedClassFixture::create(
            'App\Domain\Order',
            dependencies: ['App\Infra\Db' => 4, 'App\Http\Request' => 8, 'App\Shared\Money' => 9],
        );

        $violations = (new NotDependOnTheseNamespaces(['App\Infra', 'App\Http']))->evaluate($class, new ClassGraph());

        self::assertCount(2, $violations);
        self::assertSame([4, 8], array_map(static fn ($v) => $v->line, iterator_to_array($violations)));
    }
}
