<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\ClassDescriptions;
use Arkitect\Analyzer\DocblockTypesResolver;
use Arkitect\Analyzer\FileParser;
use Arkitect\Analyzer\FileVisitor;
use Arkitect\Analyzer\GenericError;
use Arkitect\Analyzer\ParsingErrors;
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
            TargetPhpVersion::create('8.0')
        );

        $content = '<?php
        class Foo {}
        ';

        $fileVisitor->getClassDescriptions()->willReturn([]);
        $traverser->traverse(Argument::type('array'))->shouldBeCalled();
        $result = $fileParser->parse($content, 'foo');
        self::assertInstanceOf(ClassDescriptions::class, $result);
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
            TargetPhpVersion::create('8.0')
        );

        $content = '<?php
        class Match {}
        ';

        $result = $fileParser->parse($content, 'foo');
        self::assertInstanceOf(ParsingErrors::class, $result);
    }

    public function test_parse_file_returns_generic_error_on_exception(): void
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

        $traverser->traverse(Argument::type('array'))->willThrow(new \RuntimeException('unexpected error'));

        $fileParser = new FileParser(
            $traverser->reveal(),
            $fileVisitor->reveal(),
            $nameResolver->reveal(),
            $docblockResolver->reveal(),
            TargetPhpVersion::create('8.0')
        );

        $content = '<?php
        class Foo {}
        ';

        $result = $fileParser->parse($content, 'foo');
        self::assertInstanceOf(GenericError::class, $result);
        self::assertSame('unexpected error', $result->getError());
        self::assertSame('foo', $result->getRelativeFilePath());
    }
}
