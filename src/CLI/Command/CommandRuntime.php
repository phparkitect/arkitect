<?php

declare(strict_types=1);

namespace Arkitect\CLI\Command;

use Arkitect\CLI\Progress\DebugProgress;
use Arkitect\CLI\Progress\Progress;
use Arkitect\CLI\Progress\ProgressBarProgress;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The console plumbing shared by every command that runs an analysis:
 * process limits, progress creation, the version heading and the
 * execution-time footer. Commands compose this object (like CommonOptions)
 * so the run lifecycle cannot drift between them.
 */
class CommandRuntime
{
    public function raiseLimits(): void
    {
        ini_set('memory_limit', '-1');
        ini_set('xdebug.max_nesting_level', '10000');
    }

    public function createProgress(OutputInterface $output, bool $verbose): Progress
    {
        $output->writeln('Progress: '.($verbose ? 'debug' : 'bar'));

        return $verbose ? new DebugProgress($output) : new ProgressBarProgress($output);
    }

    public function printHeadingLine(Command $command, OutputInterface $output): void
    {
        $app = $command->getApplication();

        $version = $app ? $app->getVersion() : 'unknown';

        $output->writeln("<info>PHPArkitect $version</info>\n");
    }

    public function printExecutionTime(OutputInterface $output, float $startTime): void
    {
        $endTime = microtime(true);
        $executionTime = number_format($endTime - $startTime, 2);

        $output->writeln("⏱️ Execution time: $executionTime\n");
    }
}
