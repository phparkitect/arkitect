<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

class FileParseResultCache implements ParseResultCache
{
    /** @var array<string, array{hash: string, result: ParserResult}> */
    private array $entries = [];

    private bool $dirty = false;

    private string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;

        if (file_exists($filePath)) {
            $data = unserialize((string) file_get_contents($filePath));
            if (is_array($data)) {
                $this->entries = $data;
            }
        }
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

    public function __destruct()
    {
        if ($this->dirty) {
            file_put_contents($this->filePath, serialize($this->entries));
        }
    }
}
