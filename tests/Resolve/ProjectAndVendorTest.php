<?php

declare(strict_types=1);

namespace Arkitect\Tests\Resolve;

use Arkitect\FileSystem\FilesystemFileRepository;
use Arkitect\Parser\TargetPhpVersion;
use Arkitect\ProjectParser;
use Arkitect\Resolve\ClassGraph;
use Arkitect\Resolve\Membership;
use Arkitect\Resolve\ParsedClassGraph;
use Arkitect\Tests\FileSystem\InMemoryFileRepository;
use PHPUnit\Framework\TestCase;

/**
 * The reason vendor/ gets parsed at all (see ARCHITECTURE.md, stage 1):
 * a project class can extend a vendor class, and resolving what it is-a
 * requires the vendor class's own ancestor chain. Every other Resolve test
 * builds its ClassGraph from one parsed set (either synthetic fixtures or
 * vendor/ alone) — this is the one that actually crosses the boundary,
 * using nikic/php-parser's own real inheritance as the vendor side.
 */
final class ProjectAndVendorTest extends TestCase
{
    public function test_a_project_class_resolves_against_a_real_vendor_ancestor_chain(): void
    {
        $version = TargetPhpVersion::create('8.5');

        $project = (new InMemoryFileRepository())->withFile(
            'src/MyVisitor.php',
            "<?php\nnamespace App;\nuse PhpParser\\NodeVisitorAbstract;\nclass MyVisitor extends NodeVisitorAbstract {}\n"
        );
        $projectResult = (new ProjectParser($project))->parse($version);

        $vendorResult = (new ProjectParser(new FilesystemFileRepository(__DIR__.'/../../vendor/nikic/php-parser')))
            ->parse($version);

        self::assertNotEmpty($vendorResult->classes, 'sanity check: vendor actually parsed something');

        $classGraph = new ParsedClassGraph(...$projectResult->classes, ...$vendorResult->classes);

        // direct: App\MyVisitor extends PhpParser\NodeVisitorAbstract
        self::assertSame(
            Membership::Yes,
            $classGraph->isA('App\MyVisitor', 'PhpParser\NodeVisitorAbstract')
        );

        // transitive, through a real vendor-internal edge:
        // NodeVisitorAbstract implements NodeVisitor
        self::assertSame(
            Membership::Yes,
            $classGraph->isA('App\MyVisitor', 'PhpParser\NodeVisitor')
        );
    }
}
