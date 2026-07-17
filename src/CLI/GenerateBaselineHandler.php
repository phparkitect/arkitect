<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\CLI\Progress\Progress;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Application service behind the `generate-baseline` command: loads the
 * config, runs the analysis and snapshots the current violations to a
 * baseline file. It only depends on OutputInterface for writing, so it
 * can be unit tested with a BufferedOutput.
 */
final class GenerateBaselineHandler
{
    public function __construct(private Runner $runner)
    {
    }

    public function generateBaseline(GenerateBaselineOptions $options, Progress $progress, OutputInterface $output): void
    {
        $config = ConfigBuilder::loadFromFile($options->getConfigFilePath())
            ->autoloadFilePath($options->getAutoloadFilePath())
            ->targetPhpVersion(TargetPhpVersion::create($options->getTargetPhpVersion()));

        $output->writeln("Config file '{$options->getConfigFilePath()}' found\n");

        $result = $this->runner->baseline($config, $progress);

        Baseline::save(
            $options->getBaselineFilePath(),
            $result->getViolations(),
            $options->isIgnoreBaselineLinenumbers()
        );

        $output->writeln("ℹ️ Baseline file '{$options->getBaselineFilePath()}' created!");
    }
}
