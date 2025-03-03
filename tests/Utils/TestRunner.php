<?php

declare(strict_types=1);

namespace Arkitect\Tests\Utils;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FileParser;
use Arkitect\Analyzer\FileParserFactory;
use Arkitect\ClassSet;
use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Rules\ArchRule;
use Arkitect\Rules\ParsingErrors;
use Arkitect\Rules\Violations;

class TestRunner
{
    private static $instance;

    private Violations $violations;

    private ParsingErrors $parsingErrors;

    private FileParser $fileParser;

    private function __construct(string $version = null)
    {
        $this->violations = new Violations();
        $this->parsingErrors = new ParsingErrors();
        $this->fileParser = FileParserFactory::createFileParser(TargetPhpVersion::create($version));
    }

    public static function create(string $version = null): self
    {
        if (null === self::$instance) {
            self::$instance = new self($version);
        }

        return self::$instance;
    }

    public function run(ClassSet $classSet, ArchRule ...$rules): void
    {
        $this->violations = new Violations();
        $this->parsingErrors = new ParsingErrors();

        foreach ($classSet as $file) {
            $this->fileParser->parse($file->getContents(), $file->getRelativePathname());

            $parsedErrors = $this->fileParser->getParsingErrors();

            foreach ($parsedErrors as $parsedError) {
                $this->parsingErrors->add($parsedError);
            }

            /** @var ClassDescription $classDescription */
            foreach ($this->fileParser->getClassDescriptions() as $classDescription) {
                foreach ($rules as $rule) {
                    $rule->check($classDescription, $this->violations);
                }
            }
        }
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
