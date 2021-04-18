<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;

class FileParserFactory
{
    public static function createFileParser(): FileParser
    {
        return new FileParser(
            new NodeTraverser(),
            new FileVisitor(),
            new NameResolver()
        );
    }
}
