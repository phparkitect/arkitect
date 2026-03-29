<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

use Symfony\Component\Finder\SplFileInfo;

/**
 * @template-implements \IteratorAggregate<SplFileInfo>
 */
class FilesToParse implements \IteratorAggregate
{
    /** @var array<SplFileInfo> */
    private array $files = [];

    public function add(SplFileInfo $file): void
    {
        $this->files[] = $file;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->files);
    }
}
