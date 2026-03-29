<?php

declare(strict_types=1);

namespace Arkitect\Tests\Utils;

use Arkitect\Analyzer\FileParser;
use Arkitect\Analyzer\FileParserFactory;
use Arkitect\Analyzer\ParsingErrors;
use Arkitect\ClassSet;
use Arkitect\ClassSetRules;
use Arkitect\CLI\Progress\VoidProgress;
use Arkitect\CLI\Runner;
use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Rules\ArchRule;
use Arkitect\Rules\Violations;

class TestRunner
{
    private static $instance;

    private Violations $violations;

    private ParsingErrors $parsingErrors;

    private FileParser $fileParser;

    private function __construct(?string $version = null)
    {
        $this->violations = new Violations();
        $this->parsingErrors = new ParsingErrors();
        $this->fileParser = FileParserFactory::createFileParser(
            TargetPhpVersion::create($version),
            true,
            null
        );
    }

    public static function create(?string $version = null): self
    {
        if (null === self::$instance) {
            self::$instance = new self($version);
        }

        return self::$instance;
    }

    public function run(string $srcPath, ArchRule ...$rules): void
    {
        $this->violations = new Violations();
        $this->parsingErrors = new ParsingErrors();

        $classSetRules = ClassSetRules::create(ClassSet::fromDir($srcPath), ...$rules);

        (new Runner())->check($classSetRules, new VoidProgress(), $this->fileParser, $this->violations, $this->parsingErrors, false);
    }

    public function getViolations(): Violations
    {
        return $this->violations;
    }

    public function getParsingErrors(): ParsingErrors
    {
        return $this->parsingErrors;
    }
}
