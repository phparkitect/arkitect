<?php

declare(strict_types=1);

namespace Arkitect;

interface BaselineRepository
{
    /** @throws \RuntimeException if the path was configured but holds no baseline */
    public function read(string $path): Baseline;

    public function write(string $path, Baseline $baseline): void;
}
