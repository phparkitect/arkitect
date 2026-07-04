<?php

declare(strict_types=1);

namespace Arkitect\CLI\Command;

use Arkitect\CLI\Baseline;
use Arkitect\CLI\ConfigBuilder;
use Arkitect\CLI\Printer\Printer;
use Arkitect\CLI\Printer\PrinterFactory;
use Arkitect\CLI\Runner;
use Arkitect\CLI\TargetPhpVersion;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Check extends AbstractCommand
{
    private const STOP_ON_FAILURE_PARAM = 'stop-on-failure';
    private const USE_BASELINE_PARAM = 'use-baseline';
    private const SKIP_BASELINE_PARAM = 'skip-baseline';
    private const FORMAT_PARAM = 'format';

    private const GENERATE_BASELINE_PARAM = 'generate-baseline';

    public function __construct()
    {
        parent::__construct('check');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Check that architectural rules are matched.')
            ->setHelp('This command allows you check that architectural rules defined in your config file are matched.')
            ->addOption(
                self::STOP_ON_FAILURE_PARAM,
                's',
                InputOption::VALUE_NONE,
                'Stop on failure'
            )
            ->addOption(
                self::GENERATE_BASELINE_PARAM,
                'g',
                InputOption::VALUE_OPTIONAL,
                'Moved: use the generate-baseline command instead',
                false
            )
            ->addOption(
                self::USE_BASELINE_PARAM,
                'b',
                InputOption::VALUE_REQUIRED,
                'Ignore errors in baseline file'
            )
            ->addOption(
                self::SKIP_BASELINE_PARAM,
                'k',
                InputOption::VALUE_NONE,
                'Don\'t use the default baseline'
            )
            ->addOption(
                self::FORMAT_PARAM,
                'f',
                InputOption::VALUE_OPTIONAL,
                'Output format: text (default), json, gitlab',
                'text'
            );

        $this->configureCommonOptions();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '-1');
        ini_set('xdebug.max_nesting_level', '10000');
        $startTime = microtime(true);

        try {
            $verbose = (bool) $input->getOption('verbose');
            $rulesFilename = $input->getOption(self::CONFIG_FILENAME_PARAM);
            $stopOnFailure = (bool) $input->getOption(self::STOP_ON_FAILURE_PARAM);
            $useBaseline = (string) $input->getOption(self::USE_BASELINE_PARAM);
            $skipBaseline = (bool) $input->getOption(self::SKIP_BASELINE_PARAM);
            $ignoreBaselineLinenumbers = (bool) $input->getOption(self::IGNORE_BASELINE_LINENUMBERS_PARAM);
            $phpVersion = $input->getOption(self::TARGET_PHP_PARAM);
            $format = $input->getOption(self::FORMAT_PARAM);

            // we write everything on STDERR apart from the list of violations which goes on STDOUT
            // this allows to pipe the output of this command to a file while showing output on the terminal
            $stdOut = $output;
            $output = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

            if (false !== $input->getOption(self::GENERATE_BASELINE_PARAM)) {
                $output->writeln('❌ The --generate-baseline option has been moved to its own command.');
                $output->writeln('Run: phparkitect generate-baseline [filename]');

                return self::ERROR_CODE;
            }

            if ($this->isRunningAsPhar() && null === $input->getOption(self::AUTOLOAD_PARAM)) {
                $output->writeln('❌ The --autoload option is required when running phparkitect as a PHAR');

                return self::ERROR_CODE;
            }

            $this->printHeadingLine($output);

            $config = ConfigBuilder::loadFromFile($rulesFilename)
                ->autoloadFilePath($input->getOption(self::AUTOLOAD_PARAM))
                ->stopOnFailure($stopOnFailure)
                ->targetPhpVersion(TargetPhpVersion::create($phpVersion))
                ->baselineFilePath(Baseline::resolveFilePath($useBaseline, self::DEFAULT_BASELINE_FILENAME))
                ->ignoreBaselineLinenumbers($ignoreBaselineLinenumbers)
                ->skipBaseline($skipBaseline)
                ->format($format);

            $this->requireAutoload($output, $config->getAutoloadFilePath());
            $printer = $this->createPrinter($output, $config->getFormat());
            $progress = $this->createProgress($output, $verbose);
            $baseline = $this->createBaseline($output, $config->isSkipBaseline(), $config->getBaselineFilePath());

            $output->writeln("Config file '$rulesFilename' found\n");

            $runner = new Runner();

            $result = $runner->run($config, $baseline, $progress);

            // we always print this so we do not have to do additional ifs later
            $stdOut->writeln($printer->print($result->getViolations()->groupedByFqcn()));

            if ($result->hasViolations()) {
                $output->writeln("⚠️ {$result->getViolations()->count()} violations detected!");
            }

            $staleViolationsCount = $baseline->getStaleViolationsCount();
            if ($staleViolationsCount > 0) {
                $verb = 1 === $staleViolationsCount ? 'looks' : 'look';
                $pronoun = 1 === $staleViolationsCount ? 'it' : 'them';
                $noun = 1 === $staleViolationsCount ? 'violation' : 'violations';
                $output->writeln("💡 {$staleViolationsCount} {$noun} in the baseline {$verb} fixed — regenerate the baseline to remove {$pronoun}");
            }

            if ($result->hasParsingErrors()) {
                $output->writeln('❌ found parsing errors in these files:');
                foreach ($result->getParsingErrors() as $parsingError) {
                    $output->writeln("$parsingError");
                }
            }

            !$result->hasErrors() && $output->writeln('✅ No violations detected');

            return $result->hasErrors() ? self::ERROR_CODE : self::SUCCESS_CODE;
        } catch (\Throwable $e) {
            $output->writeln("❌ {$e->getMessage()}");

            return self::ERROR_CODE;
        } finally {
            $this->printExecutionTime($output, $startTime);
        }
    }

    protected function createPrinter(OutputInterface $output, string $format): Printer
    {
        $output->writeln("Output format: $format");

        return PrinterFactory::create($format);
    }

    protected function createBaseline(OutputInterface $output, bool $skipBaseline, ?string $baselineFilePath): Baseline
    {
        $baseline = Baseline::create($skipBaseline, $baselineFilePath);

        $baseline->getFilename() && $output->writeln("Baseline file '{$baseline->getFilename()}' found");

        return $baseline;
    }
}
