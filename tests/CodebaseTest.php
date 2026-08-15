<?php

declare(strict_types=1);

namespace Arkitect\Tests;

use Arkitect\Codebase;
use Arkitect\Parser\ParseResult;
use Arkitect\Resolve\Membership;
use PHPUnit\Framework\TestCase;

/**
 * The split that lets one parse answer two different questions.
 */
final class CodebaseTest extends TestCase
{
    /**
     * vendor/ is parsed because inheritance cannot be resolved without it,
     * and left out of the rules because it is not the author's code to fix.
     */
    public function test_dependencies_resolve_but_are_not_judged(): void
    {
        $codebase = Codebase::of(new ParseResult([
            ParsedClassFixture::create('App\Order', filePath: 'src/Order.php'),
            ParsedClassFixture::create('Vendor\Base', filePath: 'vendor/acme/lib/Base.php'),
        ], []));

        self::assertSame(['App\Order'], array_map(static fn ($c) => $c->fqcn, $codebase->ownClasses));
        self::assertSame(Membership::Yes, $codebase->graph->isA('Vendor\Base', 'Vendor\Base'));
    }

    public function test_a_project_class_still_resolves_through_a_dependency(): void
    {
        $codebase = Codebase::of(new ParseResult([
            ParsedClassFixture::create('App\Service', extends: ['Vendor\Base'], filePath: 'src/Service.php'),
            ParsedClassFixture::create('Vendor\Base', implements: ['Vendor\Contract'], filePath: 'vendor/acme/Base.php'),
            ParsedClassFixture::create('Vendor\Contract', filePath: 'vendor/acme/Contract.php'),
        ], []));

        self::assertCount(1, $codebase->ownClasses);
        self::assertSame(Membership::Yes, $codebase->graph->isA('App\Service', 'Vendor\Contract'));
    }

    /**
     * The exclusion is a directory, not a prefix: a project of its own named
     * vendorish/ is the author's code.
     */
    public function test_only_the_vendor_directory_itself_is_excluded(): void
    {
        $codebase = Codebase::of(new ParseResult([
            ParsedClassFixture::create('App\A', filePath: 'vendorish/A.php'),
            ParsedClassFixture::create('App\B', filePath: 'src/vendor/B.php'),
        ], []));

        self::assertCount(2, $codebase->ownClasses);
    }
}
