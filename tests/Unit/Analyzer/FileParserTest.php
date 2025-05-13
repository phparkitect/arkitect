<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\DocblockTypesResolver;
use Arkitect\Analyzer\FileParser;
use Arkitect\Analyzer\FileVisitor;
use Arkitect\CLI\TargetPhpVersion;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class FileParserTest extends TestCase
{
    use ProphecyTrait;

    public function test_parse_file(): void
    {
        $traverser = $this->prophesize(NodeTraverser::class);
        $fileVisitor = $this->prophesize(FileVisitor::class);
        $nameResolver = $this->prophesize(NameResolver::class);
        $docblockResolver = $this->prophesize(DocblockTypesResolver::class);

        $traverser->addVisitor($nameResolver);
        $traverser->addVisitor($docblockResolver);
        $traverser->addVisitor($fileVisitor);

        $fileVisitor->setFilePath('foo')->shouldBeCalled();
        $fileVisitor->clearParsedClassDescriptions()->shouldBeCalled();

        $fileParser = new FileParser(
            $traverser->reveal(),
            $fileVisitor->reveal(),
            $nameResolver->reveal(),
            $docblockResolver->reveal(),
            TargetPhpVersion::create('7.4')
        );

        $content = '<?php
        class Foo {}
        ';

        $traverser->traverse(Argument::type('array'))->shouldBeCalled();
        $fileParser->parse($content, 'foo');
    }

    public function test_parse_file_with_name_match(): void
    {
        $traverser = $this->prophesize(NodeTraverser::class);
        $fileVisitor = $this->prophesize(FileVisitor::class);
        $nameResolver = $this->prophesize(NameResolver::class);
        $docblockResolver = $this->prophesize(DocblockTypesResolver::class);

        $traverser->addVisitor($nameResolver);
        $traverser->addVisitor($docblockResolver);
        $traverser->addVisitor($fileVisitor);

        $fileVisitor->setFilePath('foo')->shouldBeCalled();
        $fileVisitor->clearParsedClassDescriptions()->shouldBeCalled();

        $fileParser = new FileParser(
            $traverser->reveal(),
            $fileVisitor->reveal(),
            $nameResolver->reveal(),
            $docblockResolver->reveal(),
            TargetPhpVersion::create('7.4')
        );

        $content = '<?php
        class Match {}
        ';

        $traverser->traverse(Argument::type('array'))->shouldBeCalled();
        $fileParser->parse($content, 'foo');
    }
}
