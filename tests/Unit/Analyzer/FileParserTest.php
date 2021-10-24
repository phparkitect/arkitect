<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\FileParser;
use Arkitect\Analyzer\FileVisitor;
use Arkitect\CLI\TargetPhpVersion;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class FileParserTest extends TestCase
{
    public function test_parse_file(): void
    {
        $traverser = $this->prophesize(NodeTraverser::class);
        $fileVisitor = $this->prophesize(FileVisitor::class);
        $nameResolver = $this->prophesize(NameResolver::class);

        $traverser->addVisitor($nameResolver);
        $traverser->addVisitor($fileVisitor);

        $fileVisitor->clearParsedClassDescriptions()->shouldBeCalled();

        $fileParser = new FileParser(
            $traverser->reveal(),
            $fileVisitor->reveal(),
            $nameResolver->reveal(),
            TargetPhpVersion::create('7.4')
        );

        $content = '<?php
        class Foo {}
        ';

        $traverser->traverse(Argument::type('array'))->shouldBeCalled();
        $fileParser->parse($content, 'foo');
    }

    /**
     * @requires PHP < 8.0
     */
    public function test_parse_file_with_name_match(): void
    {
        $traverser = $this->prophesize(NodeTraverser::class);
        $fileVisitor = $this->prophesize(FileVisitor::class);
        $nameResolver = $this->prophesize(NameResolver::class);

        $traverser->addVisitor($nameResolver);
        $traverser->addVisitor($fileVisitor);

        $fileVisitor->clearParsedClassDescriptions()->shouldBeCalled();

        $fileParser = new FileParser(
            $traverser->reveal(),
            $fileVisitor->reveal(),
            $nameResolver->reveal(),
            TargetPhpVersion::create('7.4')
        );

        $content = '<?php
        class Match {}
        ';

        $traverser->traverse(Argument::type('array'))->shouldBeCalled();
        $fileParser->parse($content, 'foo');
    }
}
