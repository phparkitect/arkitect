<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FileParserFactory;
use Arkitect\Analyzer\FilesToParse;
use Arkitect\Analyzer\FQCNToFilePathResolver;
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
            $stopOnFailure
        );
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
        /** @var array<string, bool> $parsedAbsolutePaths */
        $parsedAbsolutePaths = [];
        /** @var array<string> $fqcnsQueue */
        $fqcnsQueue = [];
        /** @var array<string, bool> $resolvedFQCNs */
        $resolvedFQCNs = [];

        /** @var SplFileInfo $file */
        foreach ($filesToParse as $file) {
            $progress->startParsingFile($file->getRelativePathname());

            $result = $fileParser->parse($file->getContents(), $file->getRelativePathname());

            $parsedFiles->add($file->getRelativePathname(), $result);

            $realPath = $file->getRealPath();
            if (false !== $realPath) {
                $parsedAbsolutePaths[$realPath] = true;
            }

            /** @var ClassDescription $classDescription */
            foreach ($result->classDescriptions() as $classDescription) {
                $this->collectExtensionPoints($classDescription, $fqcnsQueue, $resolvedFQCNs);
            }

            $progress->endParsingFile($file->getRelativePathname());
        }

        $resolver = FQCNToFilePathResolver::create();

        while (!empty($fqcnsQueue)) {
            $fqcn = array_shift($fqcnsQueue);

            if (isset($resolvedFQCNs[$fqcn])) {
                continue;
            }
            $resolvedFQCNs[$fqcn] = true;

            $absolutePath = $resolver->resolve($fqcn);

            if (null === $absolutePath || isset($parsedAbsolutePaths[$absolutePath])) {
                continue;
            }

            $parsedAbsolutePaths[$absolutePath] = true;

            $content = file_get_contents($absolutePath);
            if (false === $content) {
                continue;
            }

            $result = $fileParser->parse($content, $absolutePath);
            $parsedFiles->add($absolutePath, $result);

            /** @var ClassDescription $classDescription */
            foreach ($result->classDescriptions() as $classDescription) {
                $this->collectExtensionPoints($classDescription, $fqcnsQueue, $resolvedFQCNs);
            }
        }

        return $parsedFiles;
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

    /**
     * @param array<string>       $queue
     * @param array<string, bool> $resolved
     */
    private function collectExtensionPoints(ClassDescription $classDescription, array &$queue, array $resolved): void
    {
        foreach ($classDescription->getInterfaces() as $interface) {
            $fqcn = $interface->toString();
            if (!isset($resolved[$fqcn])) {
                $queue[] = $fqcn;
            }
        }

        foreach ($classDescription->getExtends() as $extends) {
            $fqcn = $extends->toString();
            if (!isset($resolved[$fqcn])) {
                $queue[] = $fqcn;
            }
        }

        foreach ($classDescription->getTraits() as $trait) {
            $fqcn = $trait->toString();
            if (!isset($resolved[$fqcn])) {
                $queue[] = $fqcn;
            }
        }
    }
}
