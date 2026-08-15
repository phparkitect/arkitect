<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate\Constraint;

use Arkitect\Evaluate\Constraint\ResideInNamespace;
use Arkitect\Resolve\ParsedClassGraph;
use Arkitect\Tests\ParsedClassFixture;
use PHPUnit\Framework\TestCase;

final class ResideInNamespaceTest extends TestCase
{
    public function test_a_class_in_the_namespace_produces_no_violations(): void
    {
        $class = ParsedClassFixture::create('App\Domain\Order');

        self::assertCount(0, (new ResideInNamespace('App\Domain'))->evaluate($class, new ParsedClassGraph())->violations);
    }

    public function test_a_class_deeper_in_the_namespace_produces_no_violations(): void
    {
        $class = ParsedClassFixture::create('App\Domain\Order\Line');

        self::assertCount(0, (new ResideInNamespace('App\Domain'))->evaluate($class, new ParsedClassGraph())->violations);
    }

    public function test_a_class_elsewhere_produces_a_violation(): void
    {
        $class = ParsedClassFixture::create('App\Infra\Db\Connection');

        $violations = (new ResideInNamespace('App\Domain'))->evaluate($class, new ParsedClassGraph())->violations;

        self::assertCount(1, $violations);

        $violation = iterator_to_array($violations)[0];
        self::assertSame(ResideInNamespace::class, $violation->constraint);
        self::assertSame('does not reside in App\Domain', $violation->detail);
    }

    public function test_a_sibling_namespace_sharing_the_prefix_is_a_violation(): void
    {
        $class = ParsedClassFixture::create('App\DomainEvents\OrderPlaced');

        self::assertCount(1, (new ResideInNamespace('App\Domain'))->evaluate($class, new ParsedClassGraph())->violations);
    }

    public function test_a_wildcard_namespace_is_accepted(): void
    {
        $class = ParsedClassFixture::create('App\Modules\Billing\Domain\Invoice');

        self::assertCount(0, (new ResideInNamespace('App\*\Domain'))->evaluate($class, new ParsedClassGraph())->violations);
    }
}
