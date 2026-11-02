<?php

declare(strict_types=1);

namespace Arkitect\FileSystem;

use Arkitect\Baseline;
use Arkitect\BaselineRepository;

final class FilesystemBaselineRepository implements BaselineRepository
{
    public function __construct(private readonly string $rootPath)
    {
    }

    public function read(string $path): Baseline
    {
        $full = $this->rootPath.'/'.$path;

        // asking for a baseline that isn't there is a mistake worth stopping
        // for: carrying on with an empty one reports every known violation as
        // though it were new
        if (!is_file($full)) {
            throw new \RuntimeException(\sprintf('No baseline at "%s". Generate one, or remove it from the config.', $full));
        }

        $contents = @file_get_contents($full);

        if (false === $contents) {
            throw new \RuntimeException(\sprintf('Could not read the baseline at "%s".', $full));
        }

        return Baseline::fromJson($contents);
    }

    public function write(string $path, Baseline $baseline): void
    {
        $full = $this->rootPath.'/'.$path;

        // a write that failed and said nothing is the worst outcome here:
        // the user is told the violations were accepted, and the next run
        // reports every one of them as new
        if (false === @file_put_contents($full, $baseline->toJson())) {
            throw new \RuntimeException(\sprintf('Could not write the baseline to "%s".', $full));
        }
    }
}
