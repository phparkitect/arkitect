#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * PHPArkitect Profiling Script
 *
 * Usage:
 *   php profiling/profile.php                          # uses default phparkitect.php config
 *   php profiling/profile.php -c path/to/config.php    # uses custom config
 *   php profiling/profile.php --iterations 5           # run N iterations (avg results)
 *
 * This script measures parsing vs rule evaluation time to identify
 * where the performance bottleneck is in your PHPArkitect setup.
 */

// --- Autoload ---
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../autoload.php',
];

$autoloaded = false;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require $path;
        $autoloaded = true;
        break;
    }
}

if (!$autoloaded) {
    fwrite(STDERR, "Could not find vendor/autoload.php. Run 'composer install' first.\n");
    exit(1);
}

use Arkitect\CLI\Baseline;
use Arkitect\CLI\ConfigBuilder;
use Arkitect\CLI\ProfilingRunner;
use Arkitect\CLI\Progress\VoidProgress;
use Arkitect\CLI\TargetPhpVersion;
use Symfony\Component\Console\Output\ConsoleOutput;

// --- Parse CLI arguments ---
$options = getopt('c:', ['iterations:', 'help']);

if (isset($options['help'])) {
    echo <<<HELP
PHPArkitect Profiler

Usage:
  php profiling/profile.php [options]

Options:
  -c <file>           Config file (default: phparkitect.php)
  --iterations <N>    Number of iterations for averaging (default: 1)
  --help              Show this help message

HELP;
    exit(0);
}

$configFile = $options['c'] ?? 'phparkitect.php';
$iterations = (int) ($options['iterations'] ?? 1);

if ($iterations < 1) {
    $iterations = 1;
}

if (!file_exists($configFile)) {
    fwrite(STDERR, "Config file '$configFile' not found.\n");
    exit(1);
}

ini_set('memory_limit', '-1');

$output = new ConsoleOutput();

$output->writeln('<info>PHPArkitect Profiler</info>');
$output->writeln(sprintf('Config: %s', $configFile));
$output->writeln(sprintf('Iterations: %d', $iterations));
$output->writeln('');

// --- Run profiling ---
$allParseTimes = [];
$allRuleTimes = [];

for ($i = 1; $i <= $iterations; $i++) {
    if ($iterations > 1) {
        $output->writeln(sprintf('<comment>--- Iteration %d/%d ---</comment>', $i, $iterations));
    }

    $config = ConfigBuilder::loadFromFile($configFile)
        ->targetPhpVersion(TargetPhpVersion::latest())
        ->skipBaseline(true);

    $runner = new ProfilingRunner();
    $progress = new VoidProgress();
    $baseline = Baseline::create(true, null);

    $iterationStart = microtime(true);
    $runner->run($config, $baseline, $progress);
    $iterationTime = microtime(true) - $iterationStart;

    $allParseTimes[] = $runner->getTotalParseTime();
    $allRuleTimes[] = $runner->getTotalRuleTime();

    if ($i === $iterations) {
        // Print full report on the last iteration
        $runner->printReport($output);
    }

    if ($iterations > 1) {
        $output->writeln(sprintf(
            '  Parse: %s | Rules: %s | Total: %s',
            formatTimeShort($runner->getTotalParseTime()),
            formatTimeShort($runner->getTotalRuleTime()),
            formatTimeShort($iterationTime)
        ));
    }
}

// --- Print average if multiple iterations ---
if ($iterations > 1) {
    $avgParse = array_sum($allParseTimes) / $iterations;
    $avgRules = array_sum($allRuleTimes) / $iterations;
    $avgTotal = $avgParse + $avgRules;

    $output->writeln('');
    $output->writeln('<info>--- Average over ' . $iterations . ' iterations ---</info>');
    $output->writeln(sprintf('  Avg parsing time:     %s', formatTimeShort($avgParse)));
    $output->writeln(sprintf('  Avg rule eval time:   %s', formatTimeShort($avgRules)));
    $output->writeln(sprintf('  Avg total:            %s', formatTimeShort($avgTotal)));

    // Std deviation
    $parseStdDev = stddev($allParseTimes);
    $rulesStdDev = stddev($allRuleTimes);
    $output->writeln(sprintf('  Parse std dev:        %s', formatTimeShort($parseStdDev)));
    $output->writeln(sprintf('  Rules std dev:        %s', formatTimeShort($rulesStdDev)));
}

$output->writeln('');
$output->writeln(sprintf('Peak memory usage: %s', formatBytes(memory_get_peak_usage(true))));

// --- Helper functions ---

function formatTimeShort(float $seconds): string
{
    if ($seconds < 0.001) {
        return sprintf('%.0f us', $seconds * 1_000_000);
    }
    if ($seconds < 1.0) {
        return sprintf('%.2f ms', $seconds * 1000);
    }

    return sprintf('%.2f s', $seconds);
}

function formatBytes(int $bytes): string
{
    if ($bytes < 1024) {
        return sprintf('%d B', $bytes);
    }
    if ($bytes < 1048576) {
        return sprintf('%.1f KB', $bytes / 1024);
    }

    return sprintf('%.1f MB', $bytes / 1048576);
}

function stddev(array $values): float
{
    $n = count($values);
    if ($n < 2) {
        return 0.0;
    }
    $mean = array_sum($values) / $n;
    $sumSquaredDiffs = 0.0;
    foreach ($values as $v) {
        $sumSquaredDiffs += ($v - $mean) ** 2;
    }

    return sqrt($sumSquaredDiffs / ($n - 1));
}
