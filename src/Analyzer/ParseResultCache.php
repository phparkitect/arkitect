<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

interface ParseResultCache
{
    public function get(string $filename, string $contentHash): ?ParserResult;

    public function set(string $filename, string $contentHash, ParserResult $result): void;
}
