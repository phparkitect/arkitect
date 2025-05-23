<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

class FilePath
{
    private string $path = '';

    public function toString(): string
    {
        return $this->path;
    }

    public function set(string $path): void
    {
        $this->path = $path;
    }
}
