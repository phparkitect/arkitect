<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FileParserFactory;
use Arkitect\Analyzer\Parser;
use Arkitect\ClassSetRules;
use Arkitect\CLI\Progress\Progress;
use Arkitect\Exceptions\FailOnFirstViolationException;
use Arkitect\Rules\ParsingErrors;
use Arkitect\Rules\Violations;
use Symfony\Component\Finder\SplFileInfo;

class Runner
{
    private Violations $violations;

    private ParsingErrors $parsingErrors;

    private bool $stopOnFailure;

    public function __construct(bool $stopOnFailure = false)
    {
        $this->stopOnFailure = $stopOnFailure;
        $this->violations = new Violations();
        $this->parsingErrors = new ParsingErrors();
    }

    public function run(Config $config, Progress $progress, TargetPhpVersion $targetPhpVersion): AnalysisResult
    {
        $fileParser = FileParserFactory::createFileParser($targetPhpVersion, $config->isParseCustomAnnotationsEnabled());

        /** @var ClassSetRules $classSetRule */
        foreach ($config->getClassSetRules() as $classSetRule) {
            $progress->startFileSetAnalysis($classSetRule->getClassSet());

            try {
                $this->check($classSetRule, $progress, $fileParser, $this->violations, $this->parsingErrors);
            } catch (FailOnFirstViolationException $e) {
                break;
            } finally {
                $progress->endFileSetAnalysis($classSetRule->getClassSet());
            }
        }

        $this->violations->sort();

        return new AnalysisResult(
            $this->violations,
            $this->parsingErrors,
        );
    }

    public function check(
        ClassSetRules $classSetRule,
        Progress $progress,
        Parser $fileParser,
        Violations $violations,
        ParsingErrors $parsingErrors
    ): void {
        /** @var SplFileInfo $file */
        foreach ($classSetRule->getClassSet() as $file) {
            $fileViolations = new Violations();

            $progress->startParsingFile($file->getRelativePathname());

            $fileParser->parse($file->getContents(), $file->getRelativePathname());
            $parsedErrors = $fileParser->getParsingErrors();

            foreach ($parsedErrors as $parsedError) {
                $parsingErrors->add($parsedError);
            }

            /** @var ClassDescription $classDescription */
            foreach ($fileParser->getClassDescriptions() as $classDescription) {
                foreach ($classSetRule->getRules() as $rule) {
                    $rule->check($classDescription, $fileViolations);

                    if ($this->stopOnFailure && $fileViolations->count() > 0) {
                        $violations->merge($fileViolations);

                        throw new FailOnFirstViolationException();
                    }
                }
            }

            $violations->merge($fileViolations);

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
