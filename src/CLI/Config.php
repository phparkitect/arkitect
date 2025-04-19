<?php
declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\ClassSet;
use Arkitect\ClassSetRules;
use Arkitect\CLI\Printer\PrinterFactory;
use Arkitect\Rules\DSL\ArchRule;

class Config
{
    /** @var array<ClassSetRules> */
    private array $classSetRules;

    private bool $runOnlyARule;

    private bool $parseCustomAnnotations;

    private bool $stopOnFailure;

    private bool $skipBaseline;

    private ?string $baselineFilePath;

    private bool $ignoreBaselineLinenumbers;

    private string $format;

    private TargetPhpVersion $targetPhpVersion;

    public function __construct()
    {
        $this->classSetRules = [];
        $this->runOnlyARule = false;
        $this->parseCustomAnnotations = true;
        $this->stopOnFailure = false;
        $this->skipBaseline = false;
        $this->baselineFilePath = null;
        $this->ignoreBaselineLinenumbers = false;
        $this->format = PrinterFactory::default();
        $this->targetPhpVersion = TargetPhpVersion::latest();
    }

    public function add(ClassSet $classSet, ArchRule ...$rules): self
    {
        if ($this->runOnlyARule) {
            return $this;
        }

        /** @var ArchRule $rule */
        foreach ($rules as $rule) {
            if ($rule->isRunOnlyThis()) {
                $rules = [];
                $rules[] = $rule;

                $this->runOnlyARule = true;
                break;
            }
        }

        $this->classSetRules[] = ClassSetRules::create($classSet, ...$rules);

        return $this;
    }

    public function getClassSetRules(): array
    {
        return $this->classSetRules;
    }

    public function skipParsingCustomAnnotations(): self
    {
        $this->parseCustomAnnotations = false;

        return $this;
    }

    public function isParseCustomAnnotationsEnabled(): bool
    {
        return $this->parseCustomAnnotations;
    }

    public function targetPhpVersion(TargetPhpVersion $targetPhpVersion): self
    {
        $this->targetPhpVersion = $targetPhpVersion;

        return $this;
    }

    public function getTargetPhpVersion(): TargetPhpVersion
    {
        return $this->targetPhpVersion;
    }

    public function stopOnFailure(bool $stopOnFailure): self
    {
        $this->stopOnFailure = $stopOnFailure;

        return $this;
    }

    public function isStopOnFailure(): bool
    {
        return $this->stopOnFailure;
    }

    public function baselineFilePath(?string $baselineFilePath): self
    {
        $this->baselineFilePath = $baselineFilePath;

        return $this;
    }

    public function getBaselineFilePath(): ?string
    {
        return $this->baselineFilePath;
    }

    public function ignoreBaselineLinenumbers(bool $ignoreBaselineLinenumbers): self
    {
        $this->ignoreBaselineLinenumbers = $ignoreBaselineLinenumbers;

        return $this;
    }

    public function isIgnoreBaselineLinenumbers(): bool
    {
        return $this->ignoreBaselineLinenumbers;
    }

    public function format(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function skipBaseline(bool $skipBaseline): self
    {
        $this->skipBaseline = $skipBaseline;

        return $this;
    }

    public function isSkipBaseline(): bool
    {
        return $this->skipBaseline;
    }
}
