<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

/**
 * Caches parse results for the lifetime of the instance. On a hit it returns the
 * same ParserResult instance, so callers must treat it as read-only.
 */
class MemoizingParser implements Parser
{
    private Parser $parser;

    /** @var array<string, ParserResult> */
    private array $cache = [];

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function parse(string $fileContent, string $filename): ParserResult
    {
        // both halves are needed: the pathname Runner passes is relative to the class
        // set root, so it is not unique, and it ends up in ClassDescription::filePath
        $key = $filename."\0".md5($fileContent);

        return $this->cache[$key] ??= $this->parser->parse($fileContent, $filename);
    }
}
