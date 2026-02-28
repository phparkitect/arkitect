<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\Parser;
use Arkitect\Analyzer\ProfilingFileParser;
use Arkitect\ClassSetRules;
use Arkitect\CLI\Progress\Progress;
use Arkitect\Exceptions\FailOnFirstViolationException;
use Arkitect\Rules\ParsingErrors;
use Arkitect\Rules\Violations;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\SplFileInfo;

class ProfilingRunner extends Runner
{
    /** @var array<string, array{parse_time: float, rule_time: float, file_size: int, classes_found: int, rules_evaluated: int}> */
    private array $fileProfiles = [];

    /** @var array<string, array{time: float, evaluations: int}> */
    private array $ruleProfiles = [];

    private float $totalParseTime = 0.0;
    private float $totalRuleTime = 0.0;
    private float $totalFileDiscoveryTime = 0.0;
    private int $totalFiles = 0;
    private int $totalClasses = 0;
    private int $totalRuleEvaluations = 0;
    private int $peakMemory = 0;

    private ?ProfilingFileParser $profilingParser = null;

    public function check(
        ClassSetRules $classSetRule,
        Progress $progress,
        Parser $fileParser,
        Violations $violations,
        ParsingErrors $parsingErrors,
        bool $stopOnFailure,
    ): void {
        $discoveryStart = microtime(true);
        $files = iterator_to_array($classSetRule->getClassSet());
        $this->totalFileDiscoveryTime += microtime(true) - $discoveryStart;

        $rules = $classSetRule->getRules();

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            $fileViolations = new Violations();
            $filePath = $file->getRelativePathname();

            $progress->startParsingFile($filePath);

            // --- Measure PARSING ---
            $parseStart = microtime(true);
            $fileParser->parse($file->getContents(), $filePath);
            $parseTime = microtime(true) - $parseStart;

            $parsedErrors = $fileParser->getParsingErrors();
            foreach ($parsedErrors as $parsedError) {
                $parsingErrors->add($parsedError);
            }

            $classDescriptions = $fileParser->getClassDescriptions();

            // --- Measure RULE EVALUATION ---
            $ruleStart = microtime(true);
            $rulesEvaluated = 0;

            /** @var ClassDescription $classDescription */
            foreach ($classDescriptions as $classDescription) {
                foreach ($rules as $rule) {
                    $ruleKey = \get_class($rule);
                    $singleRuleStart = microtime(true);

                    $rule->check($classDescription, $fileViolations);
                    $rulesEvaluated++;

                    $singleRuleTime = microtime(true) - $singleRuleStart;

                    if (!isset($this->ruleProfiles[$ruleKey])) {
                        $this->ruleProfiles[$ruleKey] = ['time' => 0.0, 'evaluations' => 0];
                    }
                    $this->ruleProfiles[$ruleKey]['time'] += $singleRuleTime;
                    $this->ruleProfiles[$ruleKey]['evaluations']++;

                    if ($stopOnFailure && $fileViolations->count() > 0) {
                        $violations->merge($fileViolations);
                        throw new FailOnFirstViolationException();
                    }
                }
            }

            $ruleTime = microtime(true) - $ruleStart;

            // --- Record file profile ---
            $this->fileProfiles[$filePath] = [
                'parse_time' => $parseTime,
                'rule_time' => $ruleTime,
                'file_size' => \strlen($file->getContents()),
                'classes_found' => \count($classDescriptions),
                'rules_evaluated' => $rulesEvaluated,
            ];

            $this->totalParseTime += $parseTime;
            $this->totalRuleTime += $ruleTime;
            $this->totalFiles++;
            $this->totalClasses += \count($classDescriptions);
            $this->totalRuleEvaluations += $rulesEvaluated;

            $currentMemory = memory_get_usage(true);
            if ($currentMemory > $this->peakMemory) {
                $this->peakMemory = $currentMemory;
            }

            $violations->merge($fileViolations);

            $progress->endParsingFile($filePath);
        }
    }

    protected function doRun(Config $config, Progress $progress): array
    {
        $violations = new Violations();
        $parsingErrors = new ParsingErrors();

        // Use ProfilingFileParser instead of the regular one
        $this->profilingParser = new ProfilingFileParser(
            $config->getTargetPhpVersion(),
            $config->isParseCustomAnnotationsEnabled()
        );

        /** @var \Arkitect\ClassSetRules $classSetRule */
        foreach ($config->getClassSetRules() as $classSetRule) {
            $progress->startFileSetAnalysis($classSetRule->getClassSet());

            try {
                $this->check($classSetRule, $progress, $this->profilingParser, $violations, $parsingErrors, $config->isStopOnFailure());
            } catch (FailOnFirstViolationException $e) {
                break;
            } finally {
                $progress->endFileSetAnalysis($classSetRule->getClassSet());
            }
        }

        $violations->sort();

        return [$violations, $parsingErrors];
    }

    public function printReport(OutputInterface $output): void
    {
        $totalTime = $this->totalParseTime + $this->totalRuleTime;

        $output->writeln('');
        $output->writeln('<info>====================================================</info>');
        $output->writeln('<info>              PROFILING REPORT</info>');
        $output->writeln('<info>====================================================</info>');
        $output->writeln('');

        // --- Summary ---
        $output->writeln('<comment>--- Summary ---</comment>');
        $output->writeln(sprintf('  Files analyzed:       %d', $this->totalFiles));
        $output->writeln(sprintf('  Classes found:        %d', $this->totalClasses));
        $output->writeln(sprintf('  Rule evaluations:     %d', $this->totalRuleEvaluations));
        if (null !== $this->profilingParser) {
            $output->writeln(sprintf('  AST nodes traversed:  %d', $this->profilingParser->getTotalNodeCount()));
        }
        $output->writeln(sprintf('  Peak memory:          %s', $this->formatBytes($this->peakMemory)));
        $output->writeln('');

        // --- High-level time breakdown ---
        $output->writeln('<comment>--- Time Breakdown (high level) ---</comment>');
        $output->writeln(sprintf('  File discovery:       %s', $this->formatTime($this->totalFileDiscoveryTime)));
        $output->writeln(sprintf('  Parsing (total):      %s  (%s%%)', $this->formatTime($this->totalParseTime), $this->percentage($this->totalParseTime, $totalTime)));
        $output->writeln(sprintf('  Rule evaluation:      %s  (%s%%)', $this->formatTime($this->totalRuleTime), $this->percentage($this->totalRuleTime, $totalTime)));
        $output->writeln(sprintf('  <info>Total (parse+rules):  %s</info>', $this->formatTime($totalTime)));
        $output->writeln('');

        // --- Parsing sub-phase breakdown ---
        if (null !== $this->profilingParser) {
            $astTime = $this->profilingParser->getTotalAstTime();
            $traversalTime = $this->profilingParser->getTotalTraversalTime();
            $visitorTotals = $this->profilingParser->getVisitorTotals();

            $output->writeln('<comment>--- Parsing Sub-Phase Breakdown ---</comment>');
            $output->writeln(sprintf(
                '  AST construction:     %s  (%s%% of parsing)',
                $this->formatTime($astTime),
                $this->percentage($astTime, $this->totalParseTime)
            ));
            $output->writeln(sprintf(
                '  AST traversal:        %s  (%s%% of parsing)',
                $this->formatTime($traversalTime),
                $this->percentage($traversalTime, $this->totalParseTime)
            ));

            // Per-visitor breakdown
            $output->writeln('');
            $output->writeln('  <comment>Visitor breakdown (within traversal):</comment>');
            arsort($visitorTotals);
            foreach ($visitorTotals as $name => $time) {
                $output->writeln(sprintf(
                    '    %-25s %s  (%s%% of traversal)',
                    $name,
                    $this->formatTime($time),
                    $this->percentage($time, $traversalTime)
                ));
            }

            // Traversal overhead (time in traverser not attributable to visitors)
            $visitorSum = array_sum($visitorTotals);
            $traverserOverhead = $traversalTime - $visitorSum;
            if ($traverserOverhead > 0) {
                $output->writeln(sprintf(
                    '    %-25s %s  (%s%% of traversal)',
                    'Traverser overhead',
                    $this->formatTime($traverserOverhead),
                    $this->percentage($traverserOverhead, $traversalTime)
                ));
            }
            $output->writeln('');

            // --- Top 10 slowest files by AST parse vs traversal ---
            $perFileAst = $this->profilingParser->getPerFileAstTime();
            $perFileTraversal = $this->profilingParser->getPerFileTraversalTime();
            $perFileVisitor = $this->profilingParser->getPerFileVisitorTime();
            $perFileNodes = $this->profilingParser->getPerFileNodeCount();

            $output->writeln('<comment>--- Top 10 Slowest Files (AST Construction) ---</comment>');
            arsort($perFileAst);
            $i = 0;
            foreach ($perFileAst as $file => $time) {
                if (++$i > 10) {
                    break;
                }
                $nodes = $perFileNodes[$file] ?? 0;
                $output->writeln(sprintf(
                    '  %s  %s  (%d nodes)',
                    $this->formatTime($time),
                    $file,
                    $nodes
                ));
            }
            $output->writeln('');

            $output->writeln('<comment>--- Top 10 Slowest Files (AST Traversal) ---</comment>');
            arsort($perFileTraversal);
            $i = 0;
            foreach ($perFileTraversal as $file => $time) {
                if (++$i > 10) {
                    break;
                }
                $visitors = $perFileVisitor[$file] ?? [];
                $visitorDetail = [];
                foreach ($visitors as $vName => $vTime) {
                    $visitorDetail[] = sprintf('%s=%s', $this->shortVisitorName($vName), $this->formatTimeCompact($vTime));
                }
                $output->writeln(sprintf(
                    '  %s  %s  [%s]',
                    $this->formatTime($time),
                    $file,
                    implode(', ', $visitorDetail)
                ));
            }
            $output->writeln('');

            // --- Node count vs parse time ---
            $output->writeln('<comment>--- AST Node Count vs Parse Time ---</comment>');
            $nodeBuckets = [
                '0-50' => ['min' => 0, 'max' => 50, 'count' => 0, 'total_ast' => 0.0, 'total_trav' => 0.0],
                '50-100' => ['min' => 50, 'max' => 100, 'count' => 0, 'total_ast' => 0.0, 'total_trav' => 0.0],
                '100-200' => ['min' => 100, 'max' => 200, 'count' => 0, 'total_ast' => 0.0, 'total_trav' => 0.0],
                '200-500' => ['min' => 200, 'max' => 500, 'count' => 0, 'total_ast' => 0.0, 'total_trav' => 0.0],
                '500+' => ['min' => 500, 'max' => PHP_INT_MAX, 'count' => 0, 'total_ast' => 0.0, 'total_trav' => 0.0],
            ];
            foreach ($perFileNodes as $file => $nodeCount) {
                foreach ($nodeBuckets as &$bucket) {
                    if ($nodeCount >= $bucket['min'] && $nodeCount < $bucket['max']) {
                        $bucket['count']++;
                        $bucket['total_ast'] += $perFileAst[$file] ?? 0.0;
                        $bucket['total_trav'] += $perFileTraversal[$file] ?? 0.0;
                        break;
                    }
                }
                unset($bucket);
            }
            foreach ($nodeBuckets as $label => $bucket) {
                if ($bucket['count'] > 0) {
                    $output->writeln(sprintf(
                        '  %-10s  %3d files  avg AST: %s  avg traversal: %s',
                        $label,
                        $bucket['count'],
                        $this->formatTime($bucket['total_ast'] / $bucket['count']),
                        $this->formatTime($bucket['total_trav'] / $bucket['count'])
                    ));
                }
            }
            $output->writeln('');
        }

        // --- Averages ---
        if ($this->totalFiles > 0) {
            $output->writeln('<comment>--- Averages per file ---</comment>');
            $output->writeln(sprintf('  Avg parse time:       %s', $this->formatTime($this->totalParseTime / $this->totalFiles)));
            $output->writeln(sprintf('  Avg rule time:        %s', $this->formatTime($this->totalRuleTime / $this->totalFiles)));
            $output->writeln(sprintf('  Avg classes/file:     %.1f', $this->totalClasses / $this->totalFiles));
            $output->writeln('');
        }

        // --- Top 10 slowest files by PARSE time ---
        $output->writeln('<comment>--- Top 10 Slowest Files (Total Parse Time) ---</comment>');
        $byParseTime = $this->fileProfiles;
        uasort($byParseTime, static fn (array $a, array $b) => $b['parse_time'] <=> $a['parse_time']);
        $i = 0;
        foreach ($byParseTime as $file => $profile) {
            if (++$i > 10) {
                break;
            }
            $output->writeln(sprintf(
                '  %s  %s  (%s, %d classes)',
                $this->formatTime($profile['parse_time']),
                $file,
                $this->formatBytes($profile['file_size']),
                $profile['classes_found']
            ));
        }
        $output->writeln('');

        // --- Rule breakdown ---
        if (\count($this->ruleProfiles) > 0) {
            $output->writeln('<comment>--- Rule Type Breakdown ---</comment>');
            uasort($this->ruleProfiles, static fn (array $a, array $b) => $b['time'] <=> $a['time']);
            foreach ($this->ruleProfiles as $ruleClass => $profile) {
                $avgPerEval = $profile['evaluations'] > 0 ? $profile['time'] / $profile['evaluations'] : 0;
                $output->writeln(sprintf(
                    '  %s  %s  (%d evals, avg %s/eval)',
                    $this->formatTime($profile['time']),
                    $this->shortClassName($ruleClass),
                    $profile['evaluations'],
                    $this->formatTime($avgPerEval)
                ));
            }
            $output->writeln('');
        }

        // --- Correlation: file size vs parse time ---
        $output->writeln('<comment>--- Parse Time vs File Size Correlation ---</comment>');
        $buckets = [
            '0-1 KB' => ['min' => 0, 'max' => 1024, 'count' => 0, 'total_parse' => 0.0],
            '1-5 KB' => ['min' => 1024, 'max' => 5120, 'count' => 0, 'total_parse' => 0.0],
            '5-10 KB' => ['min' => 5120, 'max' => 10240, 'count' => 0, 'total_parse' => 0.0],
            '10-50 KB' => ['min' => 10240, 'max' => 51200, 'count' => 0, 'total_parse' => 0.0],
            '50+ KB' => ['min' => 51200, 'max' => PHP_INT_MAX, 'count' => 0, 'total_parse' => 0.0],
        ];
        foreach ($this->fileProfiles as $profile) {
            foreach ($buckets as &$bucket) {
                if ($profile['file_size'] >= $bucket['min'] && $profile['file_size'] < $bucket['max']) {
                    $bucket['count']++;
                    $bucket['total_parse'] += $profile['parse_time'];
                    break;
                }
            }
            unset($bucket);
        }
        foreach ($buckets as $label => $bucket) {
            if ($bucket['count'] > 0) {
                $avg = $bucket['total_parse'] / $bucket['count'];
                $output->writeln(sprintf(
                    '  %-10s  %3d files  avg parse: %s  total: %s',
                    $label,
                    $bucket['count'],
                    $this->formatTime($avg),
                    $this->formatTime($bucket['total_parse'])
                ));
            }
        }
        $output->writeln('');
    }

    /**
     * @return array<string, array{parse_time: float, rule_time: float, file_size: int, classes_found: int, rules_evaluated: int}>
     */
    public function getFileProfiles(): array
    {
        return $this->fileProfiles;
    }

    public function getTotalParseTime(): float
    {
        return $this->totalParseTime;
    }

    public function getTotalRuleTime(): float
    {
        return $this->totalRuleTime;
    }

    public function getProfilingParser(): ?ProfilingFileParser
    {
        return $this->profilingParser;
    }

    private function formatTime(float $seconds): string
    {
        if ($seconds < 0.001) {
            return sprintf('%6.0f us', $seconds * 1_000_000);
        }
        if ($seconds < 1.0) {
            return sprintf('%6.2f ms', $seconds * 1000);
        }

        return sprintf('%6.2f s ', $seconds);
    }

    private function formatTimeCompact(float $seconds): string
    {
        if ($seconds < 0.001) {
            return sprintf('%.0fus', $seconds * 1_000_000);
        }
        if ($seconds < 1.0) {
            return sprintf('%.1fms', $seconds * 1000);
        }

        return sprintf('%.2fs', $seconds);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return sprintf('%d B', $bytes);
        }
        if ($bytes < 1048576) {
            return sprintf('%.1f KB', $bytes / 1024);
        }

        return sprintf('%.1f MB', $bytes / 1048576);
    }

    private function percentage(float $part, float $total): string
    {
        if ($total == 0.0) {
            return '0.0';
        }

        return number_format(($part / $total) * 100, 1);
    }

    private function shortClassName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }

    private function shortVisitorName(string $name): string
    {
        $parts = explode('\\', $name);

        return end($parts);
    }
}
