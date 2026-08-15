<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate\Selector;

use Arkitect\Evaluate\Selector\NotResideInNamespace;
use Arkitect\Evaluate\Selector\Selection;
use Arkitect\Resolve\ParsedClassGraph;
use Arkitect\Tests\ParsedClassFixture;
use PHPUnit\Framework\TestCase;

final class NotResideInNamespaceTest extends TestCase
{
    public function test_a_class_outside_the_namespace_is_selected(): void
    {
        self::assertSame(Selection::Yes, $this->select('App\Infra', 'App\Domain\Order'));
    }

    public function test_a_class_inside_it_is_not(): void
    {
        self::assertSame(Selection::No, $this->select('App\Infra', 'App\Infra\Db'));
    }

    /**
     * The case that asked for this: every component except one, without
     * listing the others and keeping that list up to date.
     */
    public function test_everything_except_one_component(): void
    {
        self::assertSame(Selection::Yes, $this->select('Arkitect\Parser', 'Arkitect\Evaluate\Rule'));
        self::assertSame(Selection::Yes, $this->select('Arkitect\Parser', 'Arkitect\Report\TextReport'));
        self::assertSame(Selection::No, $this->select('Arkitect\Parser', 'Arkitect\Parser\ClassParser'));
    }

    /**
     * It excludes the namespace and everything beneath it, since that is
     * what the pattern means everywhere else.
     */
    public function test_it_excludes_what_lies_beneath_too(): void
    {
        self::assertSame(Selection::No, $this->select('Arkitect\Parser', 'Arkitect\Parser\Internal\ClassCollector'));
    }

    public function test_a_sibling_sharing_the_prefix_is_still_selected(): void
    {
        self::assertSame(Selection::Yes, $this->select('App\Domain', 'App\DomainEvents\Placed'));
    }

    private function select(string $namespace, string $fqcn): Selection
    {
        return (new NotResideInNamespace($namespace))
            ->matches(ParsedClassFixture::create($fqcn), new ParsedClassGraph());
    }
}
