<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

use Arkitect\CLI\TargetPhpVersion;
use PhpParser\NodeTraverser;

class FileParserFactory
{
    public static function createFileParser(TargetPhpVersion $targetPhpVersion, bool $parseCustomAnnotations = true): FileParser
    {
        return new FileParser(
            new NodeTraverser(),
            new FileVisitor(ClassDescriptionBuilder::create()),
            new NameResolver(null, ['parseCustomAnnotations' => $parseCustomAnnotations]),
            $targetPhpVersion
        );
    }
}
