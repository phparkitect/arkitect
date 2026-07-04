<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

class ParsedFiles
{
    private array $data = [];

    public function add(string $relativeFilePath, ParserResult $result): void
    {
        $this->data[$relativeFilePath] = $result;
    }

    public function get(string $relativeFilePath): ?ParserResult
    {
        return $this->data[$relativeFilePath] ?? null;
    }
}
