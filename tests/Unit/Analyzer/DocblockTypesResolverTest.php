<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Analyzer\DocblockTypesResolver;
use Arkitect\Analyzer\FileParser;
use Arkitect\Analyzer\FilePath;
use Arkitect\Analyzer\FileVisitor;
use Arkitect\CLI\TargetPhpVersion;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PHPUnit\Framework\TestCase;

class DocblockTypesResolverTest extends TestCase
{
    public function test_it_should_boh(): void
    {
        $parser = new FileParser(
            new NodeTraverser(),
            new FileVisitor(new ClassDescriptionBuilder()),
            new NameResolver(),
            new DocblockTypesResolver(true),
            TargetPhpVersion::latest()
        );

        $code = <<< 'EOF'
        <?php
        namespace Domain\Foo;

        use Application\MyDto;
        use Domain\ValueObject;

        class MyClass
        {
            /**
             * @param MyDto[] $dtoList
             * @param int $var2
             * @param ValueObject[] $voList
             */
            public function __construct(string $var1, array $dtoList, $var2, array $voList)
            {
            }
        }
        EOF;

        $parser->parse($code, 'boh');

        
    }
}
