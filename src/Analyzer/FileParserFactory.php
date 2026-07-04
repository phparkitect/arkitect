<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

use Arkitect\CLI\TargetPhpVersion;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;

class FileParserFactory
{
    public static function createFileParser(
        TargetPhpVersion $targetPhpVersion,
        bool $parseCustomAnnotations = true,
        ?string $cacheFilePath = null,
    ): Parser {
        $fp = new FileParser(
            new NodeTraverser(),
            new FileVisitor(new ClassDescriptionBuilder()),
            new NameResolver(),
            new DocblockTypesResolver($parseCustomAnnotations),
            $targetPhpVersion
        );

        if (null !== $cacheFilePath) {
            $fp = new CachedFileParser($fp, $cacheFilePath);
        }

        return $fp;
    }

    public static function forPhpVersion(string $targetPhpVersion): Parser
    {
        return self::createFileParser(
            TargetPhpVersion::create($targetPhpVersion),
            true,
            null
        );
    }
}
