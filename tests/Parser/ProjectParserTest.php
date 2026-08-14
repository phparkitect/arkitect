<?php

declare(strict_types=1);

namespace Arkitect\Tests\Parser;

use Arkitect\Parser\ProjectParser;
use Arkitect\Parser\TargetPhpVersion;
use Arkitect\Tests\FileSystem\InMemoryFileRepository;
use PHPUnit\Framework\TestCase;

final class ProjectParserTest extends TestCase
{
    public function test_an_empty_repository_produces_no_classes(): void
    {
        $result = (new ProjectParser(new InMemoryFileRepository()))->parse(TargetPhpVersion::create('8.5'));

        self::assertSame([], $result->classes);
        self::assertSame([], $result->errors);
    }

    public function test_a_php_file_with_a_class_is_found_with_its_repository_path(): void
    {
        $files = (new InMemoryFileRepository())->withFile('src/Foo.php', "<?php\nnamespace App;\nclass Foo {}\n");

        $result = (new ProjectParser($files))->parse(TargetPhpVersion::create('8.5'));

        self::assertCount(1, $result->classes);
        self::assertSame('App\Foo', $result->classes[0]->fqcn);
        self::assertSame('src/Foo.php', $result->classes[0]->filePath);
    }

    public function test_files_from_multiple_paths_are_all_found(): void
    {
        $files = (new InMemoryFileRepository())
            ->withFile('src/Domain/Order.php', "<?php\nnamespace App\\Domain;\nclass Order {}\n")
            ->withFile('src/Infra/Db/Connection.php', "<?php\nnamespace App\\Infra\\Db;\nclass Connection {}\n");

        $result = (new ProjectParser($files))->parse(TargetPhpVersion::create('8.5'));

        $fqcns = array_map(static fn ($c) => $c->fqcn, $result->classes);
        sort($fqcns);
        self::assertSame(['App\Domain\Order', 'App\Infra\Db\Connection'], $fqcns);
    }

    public function test_a_syntax_error_in_one_file_produces_a_parsing_error_without_stopping_the_others(): void
    {
        $files = (new InMemoryFileRepository())
            ->withFile('src/Broken.php', '<?php class {{{ broken')
            ->withFile('src/Fine.php', "<?php\nnamespace App;\nclass Fine {}\n");

        $result = (new ProjectParser($files))->parse(TargetPhpVersion::create('8.5'));

        self::assertCount(1, $result->classes);
        self::assertSame('App\Fine', $result->classes[0]->fqcn);
        self::assertNotEmpty($result->errors);
        self::assertSame('src/Broken.php', $result->errors[0]->filePath);
    }

    public function test_an_unreadable_file_produces_a_parsing_error_not_a_crash(): void
    {
        $files = (new InMemoryFileRepository())->withUnreadableFile('src/Locked.php');

        $result = (new ProjectParser($files))->parse(TargetPhpVersion::create('8.5'));

        self::assertSame([], $result->classes);
        self::assertCount(1, $result->errors);
        self::assertSame('src/Locked.php', $result->errors[0]->filePath);
    }
}
