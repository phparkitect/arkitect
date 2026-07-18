<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\CLI\Printer\PrinterFactory;
use Arkitect\CLI\Progress\Progress;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Application service behind the `check` command: loads the config,
 * runs the analysis and renders the result. It only depends on
 * OutputInterface for writing, so it can be unit tested with a
 * BufferedOutput — no console, no exit codes, no global state.
 */
final class CheckHandler
{
    public function __construct(
        private Runner $runner,
        private BaselineFileRepository $baselineRepository,
    ) {
    }

    public function check(
        CheckOptions $options,
        Progress $progress,
        OutputInterface $output,
        OutputInterface $violationsOutput,
    ): AnalysisResult {
        $config = ConfigBuilder::loadFromFile($options->getConfigFilePath())
            ->autoloadFilePath($options->getAutoloadFilePath())
            ->stopOnFailure($options->isStopOnFailure())
            ->targetPhpVersion(TargetPhpVersion::create($options->getTargetPhpVersion()))
            ->ignoreBaselineLinenumbers($options->isIgnoreBaselineLinenumbers())
            ->format($options->getFormat());

        // the baseline is optional for check: an absent file just means there
        // is nothing to ignore, so we fall back to an empty baseline
        $baselineFilePath = $options->getBaselineFilePath();
        $baseline = Baseline::empty();

        if (null !== $baselineFilePath && $this->baselineRepository->exists($baselineFilePath)) {
            $baseline = $this->baselineRepository->load($baselineFilePath);
            $output->writeln("Baseline file '$baselineFilePath' found");
        }

        $output->writeln("Config file '{$options->getConfigFilePath()}' found\n");

        $printer = PrinterFactory::create($config->getFormat());

        $result = $this->runner->run($config, $baseline, $progress);

        // we always print this so we do not have to do additional ifs later
        $violationsOutput->writeln($printer->print($result->getViolations()->groupedByFqcn()));

        if ($result->hasViolations()) {
            $output->writeln("⚠️ {$result->getViolations()->count()} violations detected!");
        }

        $this->printStaleBaselineViolations($baseline, $output);

        if ($result->hasParsingErrors()) {
            $output->writeln('❌ found parsing errors in these files:');
            foreach ($result->getParsingErrors() as $parsingError) {
                $output->writeln("$parsingError");
            }
        }

        !$result->hasErrors() && $output->writeln('✅ No violations detected');

        return $result;
    }

    private function printStaleBaselineViolations(Baseline $baseline, OutputInterface $output): void
    {
        $staleViolationsCount = $baseline->getStaleViolationsCount();

        if ($staleViolationsCount > 0) {
            $verb = 1 === $staleViolationsCount ? 'looks' : 'look';
            $pronoun = 1 === $staleViolationsCount ? 'it' : 'them';
            $noun = 1 === $staleViolationsCount ? 'violation' : 'violations';
            $output->writeln("💡 {$staleViolationsCount} {$noun} in the baseline {$verb} fixed — run `phparkitect prune-baseline` to remove {$pronoun}");
        }
    }
}
