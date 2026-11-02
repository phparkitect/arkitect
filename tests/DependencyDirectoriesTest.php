<?php

declare(strict_types=1);

namespace Arkitect\Tests;

use Arkitect\DependencyDirectories;
use PHPUnit\Framework\TestCase;

/**
 * The split that lets one parse answer two different questions: everything
 * read resolves, only the project's own code is judged.
 *
 * That the two halves work together is asserted at the other end, in
 * CheckTest::test_dependencies_resolve_without_being_checked — a project
 * class whose parent lives in vendor/ resolves, and vendor/ is not judged.
 */
final class DependencyDirectoriesTest extends TestCase
{
    /**
     * vendor/ is parsed because inheritance cannot be resolved without it,
     * and left out of the rules because it is not the author's code to fix.
     */
    public function test_dependencies_are_not_judged(): void
    {
        $own = $this->ownClasses(['vendor'], [
            'src/Order.php',
            'vendor/acme/lib/Base.php',
        ]);

        self::assertSame(['src/Order.php'], $own);
    }

    /**
     * The exclusion is a directory, not a prefix: a project of its own named
     * vendorish/ is the author's code.
     */
    public function test_a_directory_merely_starting_with_the_name_is_not_excluded(): void
    {
        self::assertSame(['vendorish/A.php'], $this->ownClasses(['vendor'], ['vendorish/A.php']));
    }

    /**
     * A pattern is anchored at the root, so a nested directory of the same
     * name is the author's code too — which is what makes a monorepo's
     * per-package vendor/ a declaration someone has to make.
     */
    public function test_a_nested_directory_of_the_same_name_is_not_excluded(): void
    {
        $own = $this->ownClasses(['vendor'], [
            'src/vendor/B.php',
            'packages/billing/vendor/acme/Dep.php',
        ]);

        self::assertCount(2, $own);
    }

    public function test_a_declared_nested_directory_is_excluded(): void
    {
        $own = $this->ownClasses(['packages/*/vendor'], [
            'packages/billing/src/Invoice.php',
            'packages/billing/vendor/acme/Dep.php',
            'packages/shipping/vendor/acme/Dep.php',
        ]);

        self::assertSame(['packages/billing/src/Invoice.php'], $own);
    }

    public function test_nothing_is_excluded_when_nothing_is_declared(): void
    {
        $directories = new DependencyDirectories();

        self::assertFalse($directories->contains('vendor/acme/lib/Base.php'));
    }

    public function test_several_directories_can_be_declared(): void
    {
        $own = $this->ownClasses(['vendor', 'build/generated'], [
            'src/Order.php',
            'vendor/acme/Base.php',
            'build/generated/Proxy.php',
        ]);

        self::assertSame(['src/Order.php'], $own);
    }

    /**
     * @param list<string> $directories
     * @param list<string> $filePaths
     *
     * @return list<string>
     */
    private function ownClasses(array $directories, array $filePaths): array
    {
        $classes = [];
        foreach ($filePaths as $i => $filePath) {
            $classes[] = ParsedClassFixture::create('App\C'.$i, filePath: $filePath);
        }

        return array_map(
            static fn ($class) => $class->filePath,
            (new DependencyDirectories(...$directories))->ownClassesIn($classes)
        );
    }
}
