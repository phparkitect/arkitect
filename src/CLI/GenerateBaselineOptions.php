<?php

declare(strict_types=1);

namespace Arkitect\CLI;

/**
 * Immutable bag of the options a `generate-baseline` run needs,
 * already parsed and resolved: no CLI concerns leak past here.
 */
class GenerateBaselineOptions
{
    public function __construct(
        private string $configFilePath,
        private ?string $targetPhpVersion,
        private ?string $autoloadFilePath,
        private bool $ignoreBaselineLinenumbers,
        private string $baselineFilePath,
    ) {
    }

    public function getConfigFilePath(): string
    {
        return $this->configFilePath;
    }

    public function getTargetPhpVersion(): ?string
    {
        return $this->targetPhpVersion;
    }

    public function getAutoloadFilePath(): ?string
    {
        return $this->autoloadFilePath;
    }

    public function isIgnoreBaselineLinenumbers(): bool
    {
        return $this->ignoreBaselineLinenumbers;
    }

    public function getBaselineFilePath(): string
    {
        return $this->baselineFilePath;
    }
}
