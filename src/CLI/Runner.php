<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Analyzer\ClassDescription;
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

    public function run(Config $config, Progress $progress, TargetPhpVersion $targetPhpVersion): void
    {
        /** @var FileParser $fileParser */
        $fileParser = FileParserFactory::createFileParser($targetPhpVersion);
        $this->violations = new Violations();
        $this->parsingErrors = new ParsingErrors();

        /** @var ClassSetRules $classSetRule */
        foreach ($config->getClassSetRules() as $classSetRule) {
            $progress->startFileSetAnalysis($classSetRule->getClassSet());

            $this->check($classSetRule, $progress, $fileParser, $this->violations, $this->parsingErrors);

            $progress->endFileSetAnalysis($classSetRule->getClassSet());
        }
    }

    public function check(
        ClassSetRules $classSetRule,
        Progress $progress,
        Parser $fileParser,
        Violations $violations,
        ParsingErrors $parsingErrors
    ): void {
        $classDescriptionsToParse = [];

        /** @var SplFileInfo $file */
        foreach ($classSetRule->getClassSet() as $file) {
            $progress->startParsingFile($file->getRelativePathname());
            $classDescriptionsToParse = $fileParser->parse(
                $file->getContents(),
                $file->getRelativePathname(),
                $classDescriptionsToParse
            );
            $parsedErrors = $fileParser->getParsingErrors();

            foreach ($parsedErrors as $parsedError) {
                $parsingErrors->add($parsedError);
            }

            $progress->endParsingFile($file->getRelativePathname());
        }

        $classDescriptionsCollection = $fileParser->getClassDescriptionsParsed();

        /** @var ClassDescription $classDescriptionToParse */
        foreach ($classDescriptionsToParse as $classDescriptionToParse) {
            $classDescriptionToParseFQCN = $classDescriptionToParse->getFQCN();

            /** @var ClassDescription $classDescriptionToParse */
            $classDescriptionToParse = $classDescriptionsCollection->get($classDescriptionToParseFQCN);

            if (null === $classDescriptionToParse) {
                continue;
            }

            foreach ($classSetRule->getRules() as $rule) {
                $rule->check($classDescriptionToParse, $violations, $classDescriptionsCollection);
            }
        }

        $errorsFromCollection = $classDescriptionsCollection->getErrors();
        foreach ($errorsFromCollection as $parsedError) {
            $parsingErrors->add($parsedError);
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
