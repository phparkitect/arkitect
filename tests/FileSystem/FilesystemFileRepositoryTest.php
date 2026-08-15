<?php

declare(strict_types=1);

namespace Arkitect\Tests\FileSystem;

use Arkitect\FileSystem\FilesystemFileRepository;
use PHPUnit\Framework\TestCase;

final class FilesystemFileRepositoryTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/arkitect-fs-repo-test-'.bin2hex(random_bytes(8));
        mkdir($this->root, recursive: true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->root);
    }

    public function test_finds_php_files_with_root_relative_paths(): void
    {
        $this->writeFile('src/Domain/Foo.php', '<?php');
        $this->writeFile('README.md', 'not php');

        $files = iterator_to_array((new FilesystemFileRepository($this->root))->files());

        self::assertSame(['src/Domain/Foo.php'], $files);
    }

    public function test_reads_file_content_by_relative_path(): void
    {
        $this->writeFile('Foo.php', '<?php class Foo {}');

        $content = (new FilesystemFileRepository($this->root))->read('Foo.php');

        self::assertSame('<?php class Foo {}', $content);
    }

    public function test_reading_an_unreadable_file_throws(): void
    {
        $this->writeFile('Locked.php', '<?php');
        chmod($this->root.'/Locked.php', 0o000);

        try {
            $this->expectException(\RuntimeException::class);
            (new FilesystemFileRepository($this->root))->read('Locked.php');
        } finally {
            chmod($this->root.'/Locked.php', 0o644);
        }
    }

    /**
     * The most ordinary mistake there is — a typo, or the wrong working
     * directory — used to surface as an UnexpectedValueException from inside
     * an iterator, halfway through a run.
     */
    public function test_a_path_that_is_not_a_directory_is_refused_at_construction(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('/does/not/exist');

        new FilesystemFileRepository('/does/not/exist');
    }

    private function removeDir(string $dir): void
    {
        foreach (scandir($dir) as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }
            $path = $dir.'/'.$entry;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function writeFile(string $relativePath, string $content): void
    {
        $fullPath = $this->root.'/'.$relativePath;
        @mkdir(\dirname($fullPath), recursive: true);
        file_put_contents($fullPath, $content);
    }
}
