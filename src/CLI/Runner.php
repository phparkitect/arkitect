<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Analyzer\ClassDescriptionIndex;
use Arkitect\Analyzer\FileParserFactory;
use Arkitect\Analyzer\FilesToParse;
use Arkitect\Analyzer\FQCNToFilePathResolver;
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
        // first step: collect all files to parse
        $filesToParse = $this->collectFilesToParse($classSetRule);

        // second step: parse all files and collect class descriptions (and parsing errors) in an index
        $classDescriptionIndex = $this->collectParsedFiles($filesToParse, $fileParser, $progress);

        // third step: enrich class descriptions with resolved dependencies
        $classDescriptionIndex->enrich();

        // fourth step: check all rules on all files
        $this->checkRulesOnParsedFiles(
            $classSetRule,
            $classDescriptionIndex,
            $violations,
            $parsingErrors,
            $stopOnFailure
        );
    }

    public function checkRulesOnParsedFiles(
        ClassSetRules $classSetRule,
        ClassDescriptionIndex $classDescriptionIndex,
        Violations $violations,
        ParsingErrors $parsingErrors,
        bool $stopOnFailure,
    ): void {
        /** @var SplFileInfo $file */
        foreach ($classSetRule->getClassSet() as $file) {
            $parsingErrors->merge($classDescriptionIndex->getParsingErrorsFor($file->getRelativePathname()));

            $fileViolations = new Violations();

            foreach ($classDescriptionIndex->getClassDescriptionsFor($file->getRelativePathname()) as $classDescription) {
                foreach ($classSetRule->getRules() as $rule) {
                    $rule->check($classDescription, $fileViolations);

                    // workaround to avoid collecting all violations if we want to stop on first failure
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

    protected function collectParsedFiles(FilesToParse $filesToParse, Parser $fileParser, Progress $progress): ClassDescriptionIndex
    {
        $classDescriptionIndex = new ClassDescriptionIndex();
        $resolver = FQCNToFilePathResolver::create();

        while ($file = $filesToParse->next()) {
            $progress->startParsingFile($file->getRelativePathname());

            $result = $fileParser->parse($file->getContents(), $file->getRelativePathname());

            $classDescriptionIndex->add($file->getRelativePathname(), $result);

            // collect extension points (interfaces, traits, parent classes) and add them to the queue
            foreach ($result->classDescriptions() as $classDescription) {
                foreach ($classDescription->getExtensionPoints() as $fqcn) {
                    $fileToParse = $resolver->resolve($fqcn);

                    if (null === $fileToParse) {
                        continue;
                    }

                    $filesToParse->add($fileToParse);
                }
            }

            $progress->endParsingFile($file->getRelativePathname());
        }

        return $classDescriptionIndex;
    }

    protected function doRun(Config $config, Progress $progress): array
    {
        $violations = new Violations();
        $parsingErrors = new ParsingErrors();

        $fileParser = FileParserFactory::createFileParser(
            $config->getTargetPhpVersion(),
            $config->isParseCustomAnnotationsEnabled(),
            $config->getCacheFilePath()
        );

        /** @var ClassSetRules $classSetRule */
        foreach ($config->getClassSetRules() as $classSetRule) {
            $progress->startFileSetAnalysis($classSetRule->getClassSet());

            try {
                $this->check(
                    $classSetRule,
                    $progress,
                    $fileParser,
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
