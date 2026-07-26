<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\CLI\Progress\Progress;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Application service behind the `prune-baseline` command: loads the
 * baseline, runs the analysis and saves back only the entries that still
 * match a current violation. Shrink-only by design — it never adds
 * anything, so unlike regenerating it cannot legitimize new violations
 * and is safe to automate.
 */
final class PruneBaselineHandler
{
    public function __construct(
        private Runner $runner,
        private BaselineFileRepository $baselineRepository,
    ) {
    }

    public function pruneBaseline(PruneBaselineOptions $options, Progress $progress, OutputInterface $output): void
    {
        // loaded before the analysis so a missing baseline fails fast
        $baseline = $this->baselineRepository->load($options->getBaselineFilePath());

        $output->writeln("Baseline file '{$options->getBaselineFilePath()}' found");

        $config = ConfigBuilder::loadFromFile($options->getConfigFilePath())
            ->autoloadFilePath($options->getAutoloadFilePath())
            ->targetPhpVersion(TargetPhpVersion::create($options->getTargetPhpVersion()));

        $output->writeln("Config file '{$options->getConfigFilePath()}' found\n");

        $result = $this->runner->baseline($config, $progress);

        $prunedBaseline = $baseline->prune($result->getViolations());

        $this->baselineRepository->save($prunedBaseline, $options->getBaselineFilePath());

        $keptCount = $prunedBaseline->getViolations()->count();
        $removedCount = $baseline->getViolations()->count() - $keptCount;

        $output->writeln("ℹ️ Baseline file '{$options->getBaselineFilePath()}' pruned: {$removedCount} removed, {$keptCount} kept");
    }
}
