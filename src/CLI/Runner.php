<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FileParserFactory;
use Arkitect\Analyzer\Parser;
use Arkitect\ClassSetRules;
use Arkitect\CLI\Progress\Progress;
use Arkitect\Exceptions\FailOnFirstViolationException;
use Arkitect\Expression\ForClasses\BeUsedOnlyBy;
use Arkitect\Rules\ParsingErrors;
use Arkitect\Rules\Violations;
use Symfony\Component\Finder\SplFileInfo;

class Runner
{
    public function run(Config $config, Baseline $baseline, Progress $progress): AnalysisResult
    {
        [$violations, $parsingErrors] = $this->doRun($config, $progress);

        $baseline->applyTo($violations, $config->isIgnoreBaselineLinenumbers());

        return new AnalysisResult(
            $violations,
            $parsingErrors,
        );
    }

    public function baseline(Config $config, Progress $progress): AnalysisResult
    {
        [$violations, $parsingErrors] = $this->doRun($config, $progress);

        return new AnalysisResult(
            $violations,
            $parsingErrors,
        );
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

                    if ($stopOnFailure && $fileViolations->count() > 0) {
                        $violations->merge($fileViolations);

                        throw new FailOnFirstViolationException();
                    }
                }
            }

            $violations->merge($fileViolations);

            $progress->endParsingFile($file->getRelativePathname());
        }
    }

    protected function doRun(Config $config, Progress $progress): array
    {
        $violations = new Violations();
        $parsingErrors = new ParsingErrors();

        $fileParser = FileParserFactory::createFileParser(
            $config->getTargetPhpVersion(),
            $config->isParseCustomAnnotationsEnabled()
        );

        // Clear the usage map before each run
        BeUsedOnlyBy::clearUsageMap();

        // First pass: collect all class dependencies for BeUsedOnlyBy
        /** @var ClassSetRules $classSetRule */
        foreach ($config->getClassSetRules() as $classSetRule) {
            /** @var SplFileInfo $file */
            foreach ($classSetRule->getClassSet() as $file) {
                $fileParser->parse($file->getContents(), $file->getRelativePathname());

                /** @var ClassDescription $classDescription */
                foreach ($fileParser->getClassDescriptions() as $classDescription) {
                    BeUsedOnlyBy::registerClassDependencies($classDescription);
                }
            }
        }

        // Second pass: evaluate rules
        /** @var ClassSetRules $classSetRule */
        foreach ($config->getClassSetRules() as $classSetRule) {
            $progress->startFileSetAnalysis($classSetRule->getClassSet());

            try {
                $this->check($classSetRule, $progress, $fileParser, $violations, $parsingErrors, $config->isStopOnFailure());
            } catch (FailOnFirstViolationException $e) {
                break;
            } finally {
                $progress->endFileSetAnalysis($classSetRule->getClassSet());
            }
        }

        $violations->sort();

        return [$violations, $parsingErrors];
    }
}
