<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\Parser;
use Arkitect\ClassSetRules;
use Arkitect\CLI\Progress\Progress;
use Arkitect\Exceptions\FailOnFirstViolationException;
use Arkitect\Rules\ParsingErrors;
use Arkitect\Rules\Violations;
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

    public function printReport(\Symfony\Component\Console\Output\OutputInterface $output): void
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
        $output->writeln(sprintf('  Peak memory:          %s', $this->formatBytes($this->peakMemory)));
        $output->writeln('');

        // --- Time breakdown ---
        $output->writeln('<comment>--- Time Breakdown ---</comment>');
        $output->writeln(sprintf('  File discovery:       %s', $this->formatTime($this->totalFileDiscoveryTime)));
        $output->writeln(sprintf('  Parsing (AST + visit):%s  (%s%%)', $this->formatTime($this->totalParseTime), $this->percentage($this->totalParseTime, $totalTime)));
        $output->writeln(sprintf('  Rule evaluation:      %s  (%s%%)', $this->formatTime($this->totalRuleTime), $this->percentage($this->totalRuleTime, $totalTime)));
        $output->writeln(sprintf('  <info>Total (parse+rules):  %s</info>', $this->formatTime($totalTime)));
        $output->writeln('');

        // --- Averages ---
        if ($this->totalFiles > 0) {
            $output->writeln('<comment>--- Averages per file ---</comment>');
            $output->writeln(sprintf('  Avg parse time:       %s', $this->formatTime($this->totalParseTime / $this->totalFiles)));
            $output->writeln(sprintf('  Avg rule time:        %s', $this->formatTime($this->totalRuleTime / $this->totalFiles)));
            $output->writeln(sprintf('  Avg classes/file:     %.1f', $this->totalClasses / $this->totalFiles));
            $output->writeln('');
        }

        // --- Top 10 slowest files by PARSE time ---
        $output->writeln('<comment>--- Top 10 Slowest Files (Parsing) ---</comment>');
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

        // --- Top 10 slowest files by RULE time ---
        $output->writeln('<comment>--- Top 10 Slowest Files (Rule Evaluation) ---</comment>');
        $byRuleTime = $this->fileProfiles;
        uasort($byRuleTime, static fn (array $a, array $b) => $b['rule_time'] <=> $a['rule_time']);
        $i = 0;
        foreach ($byRuleTime as $file => $profile) {
            if (++$i > 10) {
                break;
            }
            $output->writeln(sprintf(
                '  %s  %s  (%d rules evaluated)',
                $this->formatTime($profile['rule_time']),
                $file,
                $profile['rules_evaluated']
            ));
        }
        $output->writeln('');

        // --- Top 10 largest files ---
        $output->writeln('<comment>--- Top 10 Largest Files ---</comment>');
        $bySize = $this->fileProfiles;
        uasort($bySize, static fn (array $a, array $b) => $b['file_size'] <=> $a['file_size']);
        $i = 0;
        foreach ($bySize as $file => $profile) {
            if (++$i > 10) {
                break;
            }
            $output->writeln(sprintf(
                '  %s  %s  (parse: %s, rules: %s)',
                $this->formatBytes($profile['file_size']),
                $file,
                $this->formatTime($profile['parse_time']),
                $this->formatTime($profile['rule_time'])
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
            foreach ($buckets as $label => &$bucket) {
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
}
