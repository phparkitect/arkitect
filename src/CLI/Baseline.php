<?php
declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Rules\Violations;

class Baseline
{
    private Violations $violations;

    private string $filename;

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
        $violations->remove($this->violations, $ignoreBaselineLinenumbers);
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

        return new self(
            Violations::fromJson(file_get_contents($filename)),
            $filename
        );
    }

    public static function save(?string $filename, string $defaultFilePath, Violations $violations): string
    {
        if (null === $filename) {
            $filename = $defaultFilePath;
        }

        file_put_contents($filename, json_encode($violations, \JSON_PRETTY_PRINT));

        return $filename;
    }
}
