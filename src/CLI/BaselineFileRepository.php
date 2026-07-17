<?php

declare(strict_types=1);

namespace Arkitect\CLI;

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
        file_put_contents($filename, json_encode($baseline->getViolations(), \JSON_PRETTY_PRINT));
    }

    /**
     * Whether the default baseline file exists — this is what lets `check`
     * pick up a generated baseline automatically.
     */
    public static function hasDefaultBaseline(): bool
    {
        return file_exists(self::DEFAULT_FILENAME);
    }
}
