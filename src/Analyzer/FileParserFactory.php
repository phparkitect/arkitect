<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

use Arkitect\CLI\TargetPhpVersion;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;

class FileParserFactory
{
    public static function createFileParser(TargetPhpVersion $targetPhpVersion, bool $parseCustomAnnotations = true): FileParser
    {
        return new FileParser(
            new NodeTraverser(),
            new FileVisitor(new ClassDescriptionBuilder()),
            new NameResolver(),
            new DocblockTypesResolver($parseCustomAnnotations),
            $targetPhpVersion
        );
    }

    public static function forPhpVersion(string $targetPhpVersion): FileParser
    {
        return self::createFileParser(TargetPhpVersion::create($targetPhpVersion), true);
    }
}
