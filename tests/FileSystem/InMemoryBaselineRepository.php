<?php

declare(strict_types=1);

namespace Arkitect\Tests\FileSystem;

use Arkitect\Baseline;
use Arkitect\BaselineRepository;

final class InMemoryBaselineRepository implements BaselineRepository
{
    /** @param array<string, Baseline> $baselines */
    public function __construct(private readonly array $baselines = [])
    {
    }

    public function read(string $path): Baseline
    {
        return $this->baselines[$path] ?? throw new \RuntimeException(\sprintf('No baseline at "%s".', $path));
    }
}
