<?php

declare(strict_types=1);

namespace Arkitect\FileSystem;

interface FileRepository
{
    /** @return iterable<string> relative paths */
    public function files(): iterable;

    /** @throws \RuntimeException if the file can't be read */
    public function read(string $relativePath): string;
}
