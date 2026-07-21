<?php
declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Json;
use Arkitect\Rules\Violations;

class Baseline
{
    public const DEFAULT_FILENAME = 'phparkitect-baseline.json';

    private Violations $violations;

    private string $filename;

    private int $staleViolationsCount = 0;

    private function __construct(Violations $violations, string $filename)
    {
        $this->violations = $violations;
        $this->filename = $filename;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function applyTo(Violations $violations, bool $ignoreBaselineLinenumbers): void
    {
        $this->staleViolationsCount = $this->violations->countUnmatchedIn($violations, $ignoreBaselineLinenumbers);

        $violations->remove($this->violations, $ignoreBaselineLinenumbers);
    }

    /**
     * Number of baseline entries that no longer match any current violation,
     * i.e. that have already been fixed and could be removed from the baseline.
     * Only meaningful after applyTo() has run.
     */
    public function getStaleViolationsCount(): int
    {
        return $this->staleViolationsCount;
    }

    /**
     * @psalm-suppress RiskyTruthyFalsyComparison
     */
    public static function resolveFilePath(?string $filePath, string $defaultFilePath): ?string
    {
        if (!$filePath && file_exists($defaultFilePath)) {
            $filePath = $defaultFilePath;
        }

        return $filePath ?: null;
    }

    public static function empty(): self
    {
        return new self(new Violations(), '');
    }

    public static function create(bool $skipBaseline, ?string $baselineFilePath): self
    {
        if ($skipBaseline || null === $baselineFilePath) {
            return self::empty();
        }

        return self::loadFromFile($baselineFilePath);
    }

    public static function loadFromFile(string $filename): self
    {
        if (!file_exists($filename)) {
            throw new \RuntimeException("Baseline file '$filename' not found.");
        }

        $contents = file_get_contents($filename);

        if (false === $contents) {
            throw new \RuntimeException("Baseline file '$filename' could not be read.");
        }

        return new self(
            Violations::fromJson($contents),
            $filename
        );
    }

    public static function save(string $filename, Violations $violations, bool $ignoreLineNumbers = false): void
    {
        if ($ignoreLineNumbers) {
            $violations = $violations->withoutLineNumbers();
        }

        file_put_contents($filename, Json::encode($violations));
    }
}
