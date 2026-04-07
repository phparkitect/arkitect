<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

use Symfony\Component\Finder\SplFileInfo;

/**
 * A deduplicated FIFO queue of files to be parsed.
 */
class FilesToParse
{
    /** @var \SplQueue<SplFileInfo> */
    private \SplQueue $queue;

    /** @var array<string, true> */
    private array $seen = [];

    public function __construct()
    {
        $this->queue = new \SplQueue();
    }

    public function add(SplFileInfo $file): void
    {
        $key = $file->getRealPath() ?: $file->getPathname();

        if (isset($this->seen[$key])) {
            return;
        }

        $this->seen[$key] = true;
        $this->queue->enqueue($file);
    }

    public function isEmpty(): bool
    {
        return $this->queue->isEmpty();
    }

    public function shift(): SplFileInfo
    {
        return $this->queue->dequeue();
    }
}
