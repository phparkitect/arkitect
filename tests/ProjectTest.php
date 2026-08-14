<?php

declare(strict_types=1);

namespace Arkitect\Tests;

use Arkitect\Parser\TargetPhpVersion;
use Arkitect\Project;
use PHPUnit\Framework\TestCase;

final class ProjectTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/arkitect-project-test-'.bin2hex(random_bytes(8));
        mkdir($this->root.'/src', recursive: true);
        file_put_contents($this->root.'/src/Foo.php', "<?php\nnamespace App;\nclass Foo {}\n");
    }

    protected function tearDown(): void
    {
        unlink($this->root.'/src/Foo.php');
        rmdir($this->root.'/src');
        rmdir($this->root);
    }

    public function test_parses_a_real_project_root_end_to_end(): void
    {
        $result = Project::parse($this->root, TargetPhpVersion::create('8.5'));

        self::assertCount(1, $result->classes);
        self::assertSame('App\Foo', $result->classes[0]->fqcn);
        self::assertSame('src/Foo.php', $result->classes[0]->filePath);
    }
}
