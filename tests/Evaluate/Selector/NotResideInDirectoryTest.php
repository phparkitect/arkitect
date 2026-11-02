<?php

declare(strict_types=1);

namespace Arkitect\Tests\Evaluate\Selector;

use Arkitect\Evaluate\Selector\NotResideInDirectory;
use Arkitect\Evaluate\Selector\Selection;
use Arkitect\Resolve\ParsedClassGraph;
use Arkitect\Tests\ParsedClassFixture;
use PHPUnit\Framework\TestCase;

final class NotResideInDirectoryTest extends TestCase
{
    public function test_a_class_written_outside_the_directory_is_selected(): void
    {
        self::assertSame(Selection::Yes, $this->select('src/Infra', 'src/Domain/Order.php'));
    }

    public function test_a_class_written_inside_it_is_not(): void
    {
        self::assertSame(Selection::No, $this->select('src/Infra', 'src/Infra/Db.php'));
    }

    /**
     * The case that asked for this: a rule over the codebase except its
     * generated tree, which is what v1 spelled excludePath().
     */
    public function test_everything_except_a_generated_tree(): void
    {
        $selector = new NotResideInDirectory('src/Generated');

        self::assertSame(Selection::Yes, $this->selectWith($selector, 'src/Domain/Order.php'));
        self::assertSame(Selection::Yes, $this->selectWith($selector, 'src/Infra/Db.php'));
        self::assertSame(Selection::No, $this->selectWith($selector, 'src/Generated/Proxy.php'));
    }

    public function test_it_excludes_what_lies_beneath_too(): void
    {
        self::assertSame(Selection::No, $this->select('src/Generated', 'src/Generated/Deeply/Nested/Proxy.php'));
    }

    public function test_a_sibling_directory_sharing_the_prefix_is_still_selected(): void
    {
        self::assertSame(Selection::Yes, $this->select('src/Domain', 'src/DomainEvents/Placed.php'));
    }

    /**
     * A file at the root is in no directory, so every exclusion selects it.
     * Saying otherwise would make "everything outside src/" quietly skip
     * the classes nearest the root.
     */
    public function test_a_class_at_the_root_is_selected(): void
    {
        self::assertSame(Selection::Yes, $this->select('src', 'Kernel.php'));
    }

    public function test_one_pattern_excludes_the_same_directory_in_every_package(): void
    {
        $selector = new NotResideInDirectory('packages/*/tests');

        self::assertSame(Selection::Yes, $this->selectWith($selector, 'packages/billing/src/Invoice.php'));
        self::assertSame(Selection::No, $this->selectWith($selector, 'packages/billing/tests/InvoiceTest.php'));
        self::assertSame(Selection::No, $this->selectWith($selector, 'packages/shipping/tests/LabelTest.php'));
    }

    private function select(string $directory, string $filePath): Selection
    {
        return $this->selectWith(new NotResideInDirectory($directory), $filePath);
    }

    private function selectWith(NotResideInDirectory $selector, string $filePath): Selection
    {
        return $selector->matches(
            ParsedClassFixture::create('App\Thing', filePath: $filePath),
            new ParsedClassGraph()
        );
    }
}
