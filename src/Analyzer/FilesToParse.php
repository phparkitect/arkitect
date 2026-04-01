<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

use Symfony\Component\Finder\SplFileInfo;

/**
 * An absolute path indexed collection of files to be parsed.
 *
 * @template-implements \IteratorAggregate<SplFileInfo>
 */
class FilesToParse implements \IteratorAggregate
{
    /** @var array<string, SplFileInfo> */
    private array $files = [];

    public function add(SplFileInfo $file): void
    {
        // for vfsStream based filesystems (the one used in tests) getRealPath returns null
        $key = $file->getRealPath() ?: $file->getPathname();

        $this->files[$key] = $file;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->files);
    }
}
