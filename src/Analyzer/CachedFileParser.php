<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

class CachedFileParser implements Parser
{
    /** @var array<string, array{hash: string, result: ParserResult}> */
    private array $entries = [];

    private bool $dirty = false;

    private string $filePath;

    private Parser $innerParser;

    public function __construct(Parser $innerParser, string $cacheFilePath)
    {
        $this->filePath = $cacheFilePath;
        $this->innerParser = $innerParser;

        if (file_exists($cacheFilePath)) {
            $data = unserialize((string) file_get_contents($cacheFilePath));
            if (\is_array($data)) {
                $this->entries = $data;
            }
        }
    }

    public function __destruct()
    {
        if ($this->dirty) {
            file_put_contents($this->filePath, serialize($this->entries));
        }
    }

    public function parse(string $fileContent, string $filename): ParserResult
    {
        $cachedResult = $this->get($filename, md5($fileContent));

        if (null !== $cachedResult) {
            return $cachedResult;
        }

        $result = $this->innerParser->parse($fileContent, $filename);

        $this->set($filename, md5($fileContent), $result);

        return $result;
    }

    public function get(string $filename, string $contentHash): ?ParserResult
    {
        if (!isset($this->entries[$filename])) {
            return null;
        }

        if ($this->entries[$filename]['hash'] !== $contentHash) {
            return null;
        }

        return $this->entries[$filename]['result'];
    }

    public function set(string $filename, string $contentHash, ParserResult $result): void
    {
        $this->entries[$filename] = ['hash' => $contentHash, 'result' => $result];
        $this->dirty = true;
    }
}
