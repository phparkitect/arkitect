<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate\Selector;

use Arkitect\Evaluate\Selector\ResideInOneOfTheseNamespaces;
use Arkitect\Evaluate\Selector\Selection;
use Arkitect\Resolve\ParsedClassGraph;
use Arkitect\Tests\ParsedClassFixture;
use PHPUnit\Framework\TestCase;

/**
 * Selectors combine with and, so one rule about several namespaces could
 * not be written before this: `that()` narrows, it never widens.
 */
final class ResideInOneOfTheseNamespacesTest extends TestCase
{
    public function test_a_class_in_any_of_them_is_selected(): void
    {
        $selector = new ResideInOneOfTheseNamespaces(['App\Domain', 'App\Application']);

        self::assertSame(Selection::Yes, $this->select($selector, 'App\Domain\Order'));
        self::assertSame(Selection::Yes, $this->select($selector, 'App\Application\PlaceOrder'));
    }

    public function test_a_class_in_none_of_them_is_not_selected(): void
    {
        $selector = new ResideInOneOfTheseNamespaces(['App\Domain', 'App\Application']);

        self::assertSame(Selection::No, $this->select($selector, 'App\Infra\Db'));
    }

    public function test_the_namespaces_keep_the_matching_rules_of_a_single_one(): void
    {
        $selector = new ResideInOneOfTheseNamespaces(['App\Domain', 'App\*\Http']);

        self::assertSame(Selection::Yes, $this->select($selector, 'App\Modules\Billing\Http\Controller'));
        self::assertSame(Selection::No, $this->select($selector, 'App\DomainEvents\Placed'));
    }

    public function test_an_empty_list_selects_nothing(): void
    {
        self::assertSame(Selection::No, $this->select(new ResideInOneOfTheseNamespaces([]), 'App\Domain\Order'));
    }

    private function select(ResideInOneOfTheseNamespaces $selector, string $fqcn): Selection
    {
        return $selector->matches(ParsedClassFixture::create($fqcn), new ParsedClassGraph());
    }
}
