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

        return Baseline::fromJson((string) file_get_contents($full));
    }

    public function write(string $path, Baseline $baseline): void
    {
        file_put_contents($this->rootPath.'/'.$path, $baseline->toJson());
    }
}
