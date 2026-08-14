<?php

declare(strict_types=1);

namespace Arkitect\Tests\FileSystem;

use Arkitect\FileSystem\FileRepository;

/**
 * @internal test double
 */
final class InMemoryFileRepository implements FileRepository
{
    /** @var array<string, string> */
    private array $files = [];

    /** @var list<string> */
    private array $unreadable = [];

    public function withFile(string $relativePath, string $content): self
    {
        $clone = clone $this;
        $clone->files[$relativePath] = $content;

        return $clone;
    }

    public function withUnreadableFile(string $relativePath): self
    {
        $clone = clone $this;
        $clone->files[$relativePath] ??= '';
        $clone->unreadable[] = $relativePath;

        return $clone;
    }

    public function files(): iterable
    {
        return array_keys($this->files);
    }

    public function read(string $relativePath): string
    {
        if (\in_array($relativePath, $this->unreadable, true)) {
            throw new \RuntimeException("could not read '$relativePath'");
        }

        return $this->files[$relativePath] ?? throw new \RuntimeException("no such file '$relativePath'");
    }
}
