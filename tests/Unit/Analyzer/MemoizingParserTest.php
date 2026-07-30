<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\ClassDescriptions;
use Arkitect\Analyzer\FileParserFactory;
use Arkitect\Analyzer\MemoizingParser;
use Arkitect\Analyzer\Parser;
use Arkitect\Analyzer\ParserResult;
use Arkitect\Analyzer\ParsingErrors;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class MemoizingParserTest extends TestCase
{
    use ProphecyTrait;

    public function test_it_parses_the_same_file_only_once(): void
    {
        $result = ParserResult::withClassDescriptions(new ClassDescriptions());

        $inner = $this->prophesize(Parser::class);
        $inner->parse('<?php class Foo {}', 'Foo.php')
            ->willReturn($result)
            ->shouldBeCalledOnce();

        $parser = new MemoizingParser($inner->reveal());

        self::assertSame($result, $parser->parse('<?php class Foo {}', 'Foo.php'));
        self::assertSame($result, $parser->parse('<?php class Foo {}', 'Foo.php'));
    }

    public function test_it_does_not_confuse_different_contents_under_the_same_filename(): void
    {
        $parser = new MemoizingParser(FileParserFactory::forPhpVersion('8.1'));

        $first = $parser->parse('<?php namespace A; class Foo {}', 'Foo.php');
        $second = $parser->parse('<?php namespace B; class Foo {}', 'Foo.php');

        self::assertSame('A\Foo', $first->classDescriptions()[0]->getFQCN());
        self::assertSame('B\Foo', $second->classDescriptions()[0]->getFQCN());
    }

    public function test_it_does_not_confuse_the_same_contents_under_different_filenames(): void
    {
        $parser = new MemoizingParser(FileParserFactory::forPhpVersion('8.1'));

        $first = $parser->parse('<?php class Foo {}', 'first/Foo.php');
        $second = $parser->parse('<?php class Foo {}', 'second/Foo.php');

        self::assertSame('first/Foo.php', $first->classDescriptions()[0]->getFilePath());
        self::assertSame('second/Foo.php', $second->classDescriptions()[0]->getFilePath());
    }

    public function test_it_returns_what_the_wrapped_parser_returns(): void
    {
        $code = <<<'CODE'
        <?php
        namespace App;

        use App\Domain\Thing;

        final class Service extends Base implements Contract
        {
            public function doIt(Thing $thing): void {}
        }
        CODE;

        $memoizing = new MemoizingParser(FileParserFactory::forPhpVersion('8.1'));

        self::assertEquals(
            FileParserFactory::forPhpVersion('8.1')->parse($code, 'Service.php'),
            $memoizing->parse($code, 'Service.php')
        );
    }

    public function test_it_caches_parsing_errors_too(): void
    {
        $inner = $this->prophesize(Parser::class);
        $inner->parse('<?php class {', 'Broken.php')
            ->willReturn(ParserResult::withParsingErrors(new ParsingErrors()))
            ->shouldBeCalledOnce();

        $parser = new MemoizingParser($inner->reveal());

        $parser->parse('<?php class {', 'Broken.php');
        $parser->parse('<?php class {', 'Broken.php');
    }
}
