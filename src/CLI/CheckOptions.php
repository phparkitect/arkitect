<?php

declare(strict_types=1);

namespace Arkitect\CLI;

/**
 * Immutable bag of the options a `check` run needs, already parsed and
 * resolved: no CLI concerns (option names, tri-state flags) leak past here.
 */
final class CheckOptions
{
    public function __construct(
        private string $configFilePath,
        private ?string $targetPhpVersion,
        private bool $stopOnFailure,
        private ?string $baselineFilePath,
        private bool $skipBaseline,
        private bool $ignoreBaselineLinenumbers,
        private string $format,
        private ?string $autoloadFilePath,
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

    public function isStopOnFailure(): bool
    {
        return $this->stopOnFailure;
    }

    public function getBaselineFilePath(): ?string
    {
        return $this->baselineFilePath;
    }

    public function isSkipBaseline(): bool
    {
        return $this->skipBaseline;
    }

    public function isIgnoreBaselineLinenumbers(): bool
    {
        return $this->ignoreBaselineLinenumbers;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getAutoloadFilePath(): ?string
    {
        return $this->autoloadFilePath;
    }
}
