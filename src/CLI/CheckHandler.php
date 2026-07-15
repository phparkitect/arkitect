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
    public function __construct(private Runner $runner)
    {
    }

    public function check(
        CheckOptions $options,
        Progress $progress,
        OutputInterface $output,
        OutputInterface $violationsOutput,
    ): AnalysisResult {
        [$config, $baseline] = $this->prepare($options, $output);

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

    public function generateBaseline(CheckOptions $options, Progress $progress, OutputInterface $output): void
    {
        [$config] = $this->prepare($options, $output);

        $result = $this->runner->baseline($config, $progress);

        $baselineFilePath = Baseline::save(
            $options->getGenerateBaselineFilePath(),
            Baseline::DEFAULT_FILENAME,
            $result->getViolations(),
            $options->isIgnoreBaselineLinenumbers()
        );

        $output->writeln("ℹ️ Baseline file '$baselineFilePath' created!");
    }

    /**
     * @return array{Config, Baseline}
     */
    private function prepare(CheckOptions $options, OutputInterface $output): array
    {
        $config = ConfigBuilder::loadFromFile($options->getConfigFilePath())
            ->autoloadFilePath($options->getAutoloadFilePath())
            ->stopOnFailure($options->isStopOnFailure())
            ->targetPhpVersion(TargetPhpVersion::create($options->getTargetPhpVersion()))
            ->baselineFilePath($options->getBaselineFilePath())
            ->ignoreBaselineLinenumbers($options->isIgnoreBaselineLinenumbers())
            ->skipBaseline($options->isSkipBaseline())
            ->format($options->getFormat());

        $baseline = Baseline::create($config->isSkipBaseline(), $config->getBaselineFilePath());

        $baseline->getFilename() && $output->writeln("Baseline file '{$baseline->getFilename()}' found");

        $output->writeln("Config file '{$options->getConfigFilePath()}' found\n");

        return [$config, $baseline];
    }

    private function printStaleBaselineViolations(Baseline $baseline, OutputInterface $output): void
    {
        $staleViolationsCount = $baseline->getStaleViolationsCount();

        if ($staleViolationsCount > 0) {
            $verb = 1 === $staleViolationsCount ? 'looks' : 'look';
            $pronoun = 1 === $staleViolationsCount ? 'it' : 'them';
            $noun = 1 === $staleViolationsCount ? 'violation' : 'violations';
            $output->writeln("💡 {$staleViolationsCount} {$noun} in the baseline {$verb} fixed — regenerate the baseline to remove {$pronoun}");
        }
    }
}
