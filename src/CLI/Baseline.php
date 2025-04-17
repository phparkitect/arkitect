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

    public function getViolations(): Violations
    {
        return $this->violations;
    }

    public function applyTo(Violations $violations, bool $ignoreBaselineLinenumbers): void
    {
        $violations->remove($this->violations, $ignoreBaselineLinenumbers);
    }

    public static function empty(): self
    {
        return new self(new Violations(), '');
    }

    /**
     * @psalm-suppress RiskyTruthyFalsyComparison
     */
    public static function create(bool $skipBaseline, ?string $baselineFilePath, string $defaultFilePath): self
    {
        if ($skipBaseline) {
            return self::empty();
        }

        if (!$baselineFilePath && file_exists($defaultFilePath)) {
            $baselineFilePath = $defaultFilePath;
        }

        return $baselineFilePath ? self::loadFromFile($baselineFilePath) : self::empty();
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
