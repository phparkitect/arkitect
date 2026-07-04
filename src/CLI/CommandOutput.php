<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\CLI\Progress\DebugProgress;
use Arkitect\CLI\Progress\Progress;
use Arkitect\CLI\Progress\ProgressBarProgress;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console decorations shared by the commands: heading, progress and execution time.
 */
final class CommandOutput
{
    private OutputInterface $output;

    private float $startTime;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
        $this->startTime = microtime(true);
    }

    public function printHeading(?string $version): void
    {
        $version ??= 'unknown';

        $this->output->writeln("<info>PHPArkitect $version</info>\n");
    }

    public function createProgress(bool $verbose): Progress
    {
        $this->output->writeln('Progress: '.($verbose ? 'debug' : 'bar'));

        return $verbose ? new DebugProgress($this->output) : new ProgressBarProgress($this->output);
    }

    public function printExecutionTime(): void
    {
        $executionTime = number_format(microtime(true) - $this->startTime, 2);

        $this->output->writeln("⏱️ Execution time: $executionTime\n");
    }
}
