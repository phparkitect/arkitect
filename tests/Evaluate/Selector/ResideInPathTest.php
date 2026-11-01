<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate\Selector;

use Arkitect\Evaluate\Selector\ResideInPath;
use Arkitect\Evaluate\Selector\Selection;
use Arkitect\Resolve\ParsedClassGraph;
use Arkitect\Tests\ParsedClassFixture;
use PHPUnit\Framework\TestCase;

final class ResideInPathTest extends TestCase
{
    public function test_a_class_written_in_the_directory_is_selected(): void
    {
        $class = ParsedClassFixture::create('App\Domain\Order', filePath: 'src/Domain/Order.php');

        self::assertSame(Selection::Yes, (new ResideInPath('src/Domain'))->matches($class, new ParsedClassGraph()));
    }

    public function test_a_class_written_deeper_in_the_directory_is_selected(): void
    {
        $class = ParsedClassFixture::create('App\Domain\Order\Line', filePath: 'src/Domain/Order/Line.php');

        self::assertSame(Selection::Yes, (new ResideInPath('src/Domain'))->matches($class, new ParsedClassGraph()));
    }

    public function test_a_class_written_elsewhere_is_not_selected(): void
    {
        $class = ParsedClassFixture::create('App\Infra\Db\Connection', filePath: 'src/Infra/Db/Connection.php');

        self::assertSame(Selection::No, (new ResideInPath('src/Domain'))->matches($class, new ParsedClassGraph()));
    }

    public function test_a_sibling_directory_sharing_the_prefix_is_not_selected(): void
    {
        $class = ParsedClassFixture::create('App\DomainEvents\OrderPlaced', filePath: 'src/DomainEvents/OrderPlaced.php');

        self::assertSame(Selection::No, (new ResideInPath('src/Domain'))->matches($class, new ParsedClassGraph()));
    }

    /**
     * The case a namespace selector cannot express: the package is the
     * directory, and every package publishes the same namespace root.
     */
    public function test_one_pattern_selects_the_same_directory_in_every_package(): void
    {
        $billing = ParsedClassFixture::create('Acme\Billing\Invoice', filePath: 'packages/billing/src/Invoice.php');
        $shipping = ParsedClassFixture::create('Acme\Shipping\Label', filePath: 'packages/shipping/src/Label.php');
        $test = ParsedClassFixture::create('Acme\Billing\InvoiceTest', filePath: 'packages/billing/tests/InvoiceTest.php');

        $selector = new ResideInPath('packages/*/src');

        self::assertSame(Selection::Yes, $selector->matches($billing, new ParsedClassGraph()));
        self::assertSame(Selection::Yes, $selector->matches($shipping, new ParsedClassGraph()));
        self::assertSame(Selection::No, $selector->matches($test, new ParsedClassGraph()));
    }
}
