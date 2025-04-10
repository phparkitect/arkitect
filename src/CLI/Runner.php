<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FileParser;
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

    public function run(Config $config, Progress $progress, TargetPhpVersion $targetPhpVersion, bool $onlyErrors): void
    {
        /** @var FileParser $fileParser */
        $fileParser = FileParserFactory::createFileParser($targetPhpVersion, $config->isParseCustomAnnotationsEnabled());

        /** @var ClassSetRules $classSetRule */
        foreach ($config->getClassSetRules() as $classSetRule) {
            if (!$onlyErrors) {
                $progress->startFileSetAnalysis($classSetRule->getClassSet());
            }

            try {
                $this->check($classSetRule, $progress, $fileParser, $this->violations, $this->parsingErrors, $onlyErrors);
            } catch (FailOnFirstViolationException $e) {
                return;
            }

            if (!$onlyErrors) {
                $progress->endFileSetAnalysis($classSetRule->getClassSet());
            }
        }
    }

    public function check(
        ClassSetRules $classSetRule,
        Progress $progress,
        Parser $fileParser,
        Violations $violations,
        ParsingErrors $parsingErrors,
        bool $onlyErrors = false
    ): void {
        /** @var SplFileInfo $file */
        foreach ($classSetRule->getClassSet() as $file) {
            $fileViolations = new Violations();

            if (!$onlyErrors) {
                $progress->startParsingFile($file->getRelativePathname());
            }

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

            if (!$onlyErrors) {
                $progress->endParsingFile($file->getRelativePathname());
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
