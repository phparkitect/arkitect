<?php

declare(strict_types=1);

namespace Arkitect\Tests\Parser;

use Arkitect\Parser\ProjectParser;
use Arkitect\Parser\TargetPhpVersion;
use PHPUnit\Framework\TestCase;

final class ProjectParserTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/arkitect-project-parser-test-'.bin2hex(random_bytes(8));
        mkdir($this->root, recursive: true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->root);
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

    public function test_an_empty_directory_produces_no_classes(): void
    {
        $result = (new ProjectParser())->parse($this->root, TargetPhpVersion::create('8.5'));

        self::assertSame([], $result->classes);
        self::assertSame([], $result->errors);
    }

    public function test_a_php_file_with_a_class_is_found_with_a_root_relative_path(): void
    {
        $this->writeFile('src/Foo.php', "<?php\nnamespace App;\nclass Foo {}\n");

        $result = (new ProjectParser())->parse($this->root, TargetPhpVersion::create('8.5'));

        self::assertCount(1, $result->classes);
        self::assertSame('App\Foo', $result->classes[0]->fqcn);
        self::assertSame('src/Foo.php', $result->classes[0]->filePath);
    }

    public function test_files_across_nested_directories_are_all_found(): void
    {
        $this->writeFile('src/Domain/Order.php', "<?php\nnamespace App\\Domain;\nclass Order {}\n");
        $this->writeFile('src/Infra/Db/Connection.php', "<?php\nnamespace App\\Infra\\Db;\nclass Connection {}\n");

        $result = (new ProjectParser())->parse($this->root, TargetPhpVersion::create('8.5'));

        $fqcns = array_map(static fn ($c) => $c->fqcn, $result->classes);
        sort($fqcns);
        self::assertSame(['App\Domain\Order', 'App\Infra\Db\Connection'], $fqcns);
    }

    public function test_non_php_files_are_ignored(): void
    {
        $this->writeFile('src/Foo.php', "<?php\nnamespace App;\nclass Foo {}\n");
        $this->writeFile('README.md', '# not php');
        $this->writeFile('composer.json', '{}');

        $result = (new ProjectParser())->parse($this->root, TargetPhpVersion::create('8.5'));

        self::assertCount(1, $result->classes);
    }

    public function test_a_syntax_error_in_one_file_produces_a_parsing_error_without_stopping_the_others(): void
    {
        $this->writeFile('src/Broken.php', '<?php class {{{ broken');
        $this->writeFile('src/Fine.php', "<?php\nnamespace App;\nclass Fine {}\n");

        $result = (new ProjectParser())->parse($this->root, TargetPhpVersion::create('8.5'));

        self::assertCount(1, $result->classes);
        self::assertSame('App\Fine', $result->classes[0]->fqcn);
        self::assertNotEmpty($result->errors);
        self::assertSame('src/Broken.php', $result->errors[0]->filePath);
    }

    public function test_an_unreadable_file_produces_a_parsing_error_not_a_crash(): void
    {
        $this->writeFile('src/Locked.php', "<?php\nnamespace App;\nclass Locked {}\n");
        chmod($this->root.'/src/Locked.php', 0000);

        try {
            $result = (new ProjectParser())->parse($this->root, TargetPhpVersion::create('8.5'));
        } finally {
            chmod($this->root.'/src/Locked.php', 0644);
        }

        self::assertSame([], $result->classes);
        self::assertCount(1, $result->errors);
        self::assertSame('src/Locked.php', $result->errors[0]->filePath);
    }
}
