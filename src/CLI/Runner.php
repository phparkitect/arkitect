<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FileParserFactory;
use Arkitect\Analyzer\FilesToParse;
use Arkitect\Analyzer\ParsedFiles;
use Arkitect\Analyzer\Parser;
use Arkitect\Analyzer\ParsingErrors;
use Arkitect\ClassSetRules;
use Arkitect\CLI\Progress\Progress;
use Arkitect\Exceptions\FailOnFirstViolationException;
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
        bool $stopOnFailure,
    ): void {
    }

    public function checkRulesOnParsedFiles(
        ClassSetRules $classSetRule,
        ParsedFiles $parsedFiles,
        Violations $violations,
        ParsingErrors $parsingErrors,
        bool $stopOnFailure,
    ): void {
        /** @var SplFileInfo $file */
        foreach ($classSetRule->getClassSet() as $file) {
            $result = $parsedFiles->get($file->getRelativePathname());

            if (null === $result) {
                continue; // this should not happen
            }

            $parsingErrors->merge($result->parsingErrors());

            $fileViolations = new Violations();

            /** @var ClassDescription $classDescription */
            foreach ($result->classDescriptions() as $classDescription) {
                foreach ($classSetRule->getRules() as $rule) {
                    $rule->check($classDescription, $fileViolations);

                    if ($stopOnFailure && $fileViolations->count() > 0) {
                        $violations->merge($fileViolations);

                        throw new FailOnFirstViolationException();
                    }
                }
            }

            $violations->merge($fileViolations);
        }
    }

    protected function collectFilesToParse(ClassSetRules $classSetRule): FilesToParse
    {
        $filesToParse = new FilesToParse();

        /** @var SplFileInfo $file */
        foreach ($classSetRule->getClassSet() as $file) {
            $filesToParse->add($file);
        }

        return $filesToParse;
    }

    protected function collectParsedFiles(FilesToParse $filesToParse, Parser $fileParser, Progress $progress): ParsedFiles
    {
        $parsedFiles = new ParsedFiles();

        /** @var SplFileInfo $file */
        foreach ($filesToParse as $file) {
            $progress->startParsingFile($file->getRelativePathname());

            $result = $fileParser->parse($file->getContents(), $file->getRelativePathname());

            $parsedFiles->add($file->getRelativePathname(), $result);

            $progress->endParsingFile($file->getRelativePathname());
        }

        return $parsedFiles;
    }

    protected function doRun(Config $config, Progress $progress): array
    {
        $violations = new Violations();
        $parsingErrors = new ParsingErrors();

        $fileParser = FileParserFactory::createFileParser(
            $config->getTargetPhpVersion(),
            $config->isParseCustomAnnotationsEnabled()
        );

        /** @var ClassSetRules $classSetRule */
        foreach ($config->getClassSetRules() as $classSetRule) {
            $progress->startFileSetAnalysis($classSetRule->getClassSet());

            try {
                // first step: collect all files to parse
                $filesToParse = $this->collectFilesToParse($classSetRule);

                // second step: parse all files and collect results
                $parsedFiles = $this->collectParsedFiles(
                    $filesToParse,
                    $fileParser,
                    $progress
                );

                // third step: check all rules on all files
                $this->checkRulesOnParsedFiles(
                    $classSetRule,
                    $parsedFiles,
                    $violations,
                    $parsingErrors,
                    $config->isStopOnFailure()
                );
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
