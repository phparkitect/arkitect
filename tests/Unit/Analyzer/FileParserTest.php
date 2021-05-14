<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\FileParser;
use Arkitect\Analyzer\FileVisitor;
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

        $fileParser = new FileParser($traverser->reveal(), $fileVisitor->reveal(), $nameResolver->reveal());

        $content = '<?php
        class Foo {}
        ';

        $traverser->traverse(Argument::type('array'))->shouldBeCalled();
        $fileParser->parse($content);
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

        $fileParser = new FileParser($traverser->reveal(), $fileVisitor->reveal(), $nameResolver->reveal());

        $content = '<?php
        class Match {}
        ';

        $traverser->traverse(Argument::type('array'))->shouldBeCalled();
        $fileParser->parse($content);
    }

    public function test_parse_file_with_new_classes_inside(): void
    {
        $traverser = $this->prophesize(NodeTraverser::class);
        $fileVisitor = $this->prophesize(FileVisitor::class);
        $nameResolver = $this->prophesize(NameResolver::class);

        $traverser->addVisitor($nameResolver);
        $traverser->addVisitor($fileVisitor);

        $fileVisitor->clearParsedClassDescriptions()->shouldBeCalled();

        $fileParser = new FileParser($traverser->reveal(), $fileVisitor->reveal(), $nameResolver->reveal());

        $content = '<?php
    class Foo {
        public function bar(): void
        {
            $projector2 = new class() extends Projector
            {
                public function applyDummyDomainEvent(int $anInteger): void
                {
                    // Noop
                }

                public function getEventsTypes(): string
                {
                    return "";
                }
            };

            $projector = new Proj();
            $projector->getEventsTypes();
         }
    }
    class Proj {
        public function getEventsTypes(): string
        {
           return "";
        }
    }
    class Projector {

    }
        ';

        $traverser->traverse(Argument::type('array'))->shouldBeCalled();
        $fileParser->parse($content);
    }
}
