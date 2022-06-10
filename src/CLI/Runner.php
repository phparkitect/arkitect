<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Analyzer\FileParser;
use Arkitect\Analyzer\FileParserFactory;
use Arkitect\Analyzer\Parser;
use Arkitect\ClassSetRules;
use Arkitect\CLI\Progress\Progress;
use Arkitect\Rules\ParsingErrors;
use Arkitect\Rules\Violations;
use Symfony\Component\Finder\SplFileInfo;

class Runner
{
    /** @var Violations */
    private $violations;

    /** @var ParsingErrors */
    private $parsingErrors;

    public function __construct()
    {
        $this->violations = new Violations();
        $this->parsingErrors = new ParsingErrors();
    }

    public function run(
        Config $config,
        Progress $progress,
        TargetPhpVersion $targetPhpVersion,
        bool $stopOnFailure
    ): void {
        /** @var FileParser $fileParser */
        $fileParser = FileParserFactory::createFileParser($targetPhpVersion);

        /** @var ClassSetRules $classSetRule */
        foreach ($config->getClassSetRules() as $classSetRule) {
            $progress->startFileSetAnalysis($classSetRule->getClassSet());

            $this->check($classSetRule, $progress, $fileParser, $this->violations, $this->parsingErrors, $stopOnFailure);

            $progress->endFileSetAnalysis($classSetRule->getClassSet());

            if ($stopOnFailure && $this->violations->count() > 0) {
                return;
            }
        }
    }

    public function check(
        ClassSetRules $classSetRule,
        Progress $progress,
        Parser $fileParser,
        Violations $violations,
        ParsingErrors $parsingErrors,
        bool $stopOnFailure
    ): void {
        /** @var SplFileInfo $file */
        foreach ($classSetRule->getClassSet() as $file) {
            $progress->startParsingFile($file->getRelativePathname());

            $fileParser->parse($file->getContents(), $file->getRelativePathname());
            $parsedErrors = $fileParser->getParsingErrors();

            foreach ($parsedErrors as $parsedError) {
                $parsingErrors->add($parsedError);
            }

            foreach ($fileParser->getClassDescriptions() as $classDescription) {
                foreach ($classSetRule->getRules() as $rule) {
                    $rule->check($classDescription, $violations, $stopOnFailure);

                    if ($stopOnFailure && $violations->count() > 0) {
                        return;
                    }
                }
            }

            $progress->endParsingFile($file->getRelativePathname());
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
