#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Standalone benchmark script — works on both current and 0.8.0 versions.
 *
 * Measures total wall-clock time for parsing + rule evaluation,
 * with separate timing for each phase.
 *
 * Usage:
 *   php profiling/benchmark.php
 *   php profiling/benchmark.php --iterations 3
 */

require __DIR__ . '/../vendor/autoload.php';

use Arkitect\Analyzer\FileParserFactory;
use Arkitect\CLI\Baseline;
use Arkitect\CLI\Config;
use Arkitect\CLI\ConfigBuilder;
use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Rules\ParsingErrors;
use Arkitect\Rules\Violations;

$options = getopt('', ['iterations:', 'help']);

if (isset($options['help'])) {
    echo "Usage: php profiling/benchmark.php [--iterations N]\n";
    exit(0);
}

$iterations = (int) ($options['iterations'] ?? 3);
if ($iterations < 1) {
    $iterations = 1;
}

ini_set('memory_limit', '-1');

$configFile = __DIR__ . '/../phparkitect.php';

echo "=== PHPArkitect Benchmark ===\n";
echo "Config:     $configFile\n";
echo "Iterations: $iterations\n";
echo "\n";

$allTotalTimes = [];
$allParseTimes = [];
$allRuleTimes = [];
$allFileCount = [];

for ($iter = 1; $iter <= $iterations; $iter++) {
    // Reload config each iteration
    $config = ConfigBuilder::loadFromFile($configFile);

    $targetPhpVersion = $config->getTargetPhpVersion();
    $fileParser = FileParserFactory::createFileParser($targetPhpVersion);

    $totalParseTime = 0.0;
    $totalRuleTime = 0.0;
    $fileCount = 0;

    foreach ($config->getClassSetRules() as $classSetRule) {
        foreach ($classSetRule->getClassSet() as $file) {
            // --- Parse phase ---
            $t0 = microtime(true);
            $fileParser->parse($file->getContents(), $file->getRelativePathname());
            $parseTime = microtime(true) - $t0;
            $totalParseTime += $parseTime;

            // --- Rule evaluation phase ---
            $t1 = microtime(true);
            $fileViolations = new Violations();
            foreach ($fileParser->getClassDescriptions() as $classDescription) {
                foreach ($classSetRule->getRules() as $rule) {
                    $rule->check($classDescription, $fileViolations);
                }
            }
            $ruleTime = microtime(true) - $t1;
            $totalRuleTime += $ruleTime;

            $fileCount++;
        }
    }

    $totalTime = $totalParseTime + $totalRuleTime;

    $allTotalTimes[] = $totalTime;
    $allParseTimes[] = $totalParseTime;
    $allRuleTimes[] = $totalRuleTime;
    $allFileCount[] = $fileCount;

    printf(
        "  Iter %d/%d: files=%d  parse=%.2f ms  rules=%.2f ms  total=%.2f ms\n",
        $iter,
        $iterations,
        $fileCount,
        $totalParseTime * 1000,
        $totalRuleTime * 1000,
        $totalTime * 1000
    );
}

echo "\n--- Results (average of $iterations iterations) ---\n";

$avgParse = array_sum($allParseTimes) / $iterations;
$avgRules = array_sum($allRuleTimes) / $iterations;
$avgTotal = array_sum($allTotalTimes) / $iterations;
$avgFiles = array_sum($allFileCount) / $iterations;

printf("  Files analyzed:    %.0f\n", $avgFiles);
printf("  Avg parse time:    %.2f ms\n", $avgParse * 1000);
printf("  Avg rule time:     %.2f ms\n", $avgRules * 1000);
printf("  Avg total time:    %.2f ms\n", $avgTotal * 1000);
printf("  Parse %%:           %.1f%%\n", ($avgParse / $avgTotal) * 100);
printf("  Rules %%:           %.1f%%\n", ($avgRules / $avgTotal) * 100);
printf("  Peak memory:       %.1f MB\n", memory_get_peak_usage(true) / 1048576);
echo "\n";
