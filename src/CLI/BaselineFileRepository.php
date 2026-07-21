<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Json;
use Arkitect\Rules\Violations;

/**
 * Loads and saves Baselines as JSON files. All the filesystem concerns of
 * the baseline live here, so Baseline itself stays a pure domain object.
 */
final class BaselineFileRepository
{
    public const DEFAULT_FILENAME = 'phparkitect-baseline.json';

    public function load(string $filename): Baseline
    {
        if (!file_exists($filename)) {
            throw new \RuntimeException("Baseline file '$filename' not found.");
        }

        return Baseline::fromViolations(Violations::fromJson((string) file_get_contents($filename)));
    }

    public function save(Baseline $baseline, string $filename): void
    {
        file_put_contents($filename, Json::encode($baseline->getViolations()));
    }

    /**
     * The baseline at $filename, or null when the file is absent — for
     * callers (like check) where the baseline is optional and a missing
     * one just means "nothing to ignore".
     */
    public function loadIfPresent(string $filename): ?Baseline
    {
        return file_exists($filename) ? $this->load($filename) : null;
    }
}
