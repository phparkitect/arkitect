<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Json;
use Arkitect\Rules\Violations;

/**
 * Loads and saves Baselines as JSON files. All the filesystem concerns of
 * the baseline live here, so Baseline itself stays a pure domain object.
 */
class BaselineFileRepository
{
    public const DEFAULT_FILENAME = 'phparkitect-baseline.json';

    public function load(string $filename): Baseline
    {
        if (!file_exists($filename)) {
            throw new \RuntimeException("Baseline file '$filename' not found.");
        }

        $contents = file_get_contents($filename);

        if (false === $contents) {
            throw new \RuntimeException("Baseline file '$filename' could not be read.");
        }

        return Baseline::fromViolations(Violations::fromJson($contents));
    }

    public function save(Baseline $baseline, string $filename): void
    {
        file_put_contents($filename, Json::encode($baseline->getViolations()));
    }
}
