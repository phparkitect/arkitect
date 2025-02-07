<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\FileParser;
use Arkitect\Analyzer\FileVisitor;
use Arkitect\Analyzer\NameResolver;
use Arkitect\CLI\TargetPhpVersion;
use PhpParser\NodeTraverser;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophet;

class FileParserTest extends TestCase
{
    public function test_parse_file(): void
    {
        $prophet = new Prophet();
        $traverser = $prophet->prophesize(NodeTraverser::class);
        $fileVisitor = $prophet->prophesize(FileVisitor::class);
        $nameResolver = $prophet->prophesize(NameResolver::class);

        $traverser->addVisitor($nameResolver);
        $traverser->addVisitor($fileVisitor);

        $fileVisitor->clearParsedClassDescriptions()->shouldBeCalled();

        $fileParser = new FileParser(
            $traverser->reveal(),
            $fileVisitor->reveal(),
            $nameResolver->reveal(),
            TargetPhpVersion::create('8.1')
        );

        $content = '<?php
        class Foo {}
        ';

        $traverser->traverse(Argument::type('array'))->shouldBeCalled();
        $fileParser->parse($content, 'foo');

        $this->expectNotToPerformAssertions();
    }
}
