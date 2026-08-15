<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate\Selector;

use Arkitect\Evaluate\Selector\ResideInNamespace;
use Arkitect\Evaluate\Selector\Selection;
use Arkitect\Resolve\ClassGraph;
use Arkitect\Tests\ParsedClassFixture;
use PHPUnit\Framework\TestCase;

final class ResideInNamespaceTest extends TestCase
{
    public function test_a_class_in_the_namespace_is_selected(): void
    {
        $class = ParsedClassFixture::create('App\Domain\Order');

        self::assertSame(Selection::Yes, (new ResideInNamespace('App\Domain'))->matches($class, new ClassGraph()));
    }

    public function test_a_class_deeper_in_the_namespace_is_selected(): void
    {
        $class = ParsedClassFixture::create('App\Domain\Order\Line');

        self::assertSame(Selection::Yes, (new ResideInNamespace('App\Domain'))->matches($class, new ClassGraph()));
    }

    public function test_a_class_elsewhere_is_not_selected(): void
    {
        $class = ParsedClassFixture::create('App\Infra\Db\Connection');

        self::assertSame(Selection::No, (new ResideInNamespace('App\Domain'))->matches($class, new ClassGraph()));
    }

    public function test_a_sibling_namespace_sharing_the_prefix_is_not_selected(): void
    {
        $class = ParsedClassFixture::create('App\DomainEvents\OrderPlaced');

        self::assertSame(Selection::No, (new ResideInNamespace('App\Domain'))->matches($class, new ClassGraph()));
    }
}
