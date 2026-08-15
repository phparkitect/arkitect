<?php

declare(strict_types=1);

namespace Arkitect\FileSystem;

final class FilesystemFileRepository implements FileRepository
{
    public function __construct(
        private readonly string $rootPath,
    ) {
        // a wrong path is the most ordinary mistake there is, and letting the
        // iterator raise it mid-run turns a typo into a stack trace
        if (!is_dir($rootPath)) {
            throw new \InvalidArgumentException(\sprintf('"%s" is not a directory.', $rootPath));
        }
    }

    public function files(): iterable
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->rootPath, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }

            yield substr($file->getPathname(), \strlen($this->rootPath) + 1);
        }
    }

    public function read(string $relativePath): string
    {
        $content = @file_get_contents($this->rootPath.'/'.$relativePath);

        if (false === $content) {
            throw new \RuntimeException("could not read '$relativePath' under '$this->rootPath'");
        }

        return $content;
    }
}
