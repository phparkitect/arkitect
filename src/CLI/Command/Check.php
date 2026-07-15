<?php

declare(strict_types=1);

namespace Arkitect\CLI\Command;

use Arkitect\CLI\Autoloader;
use Arkitect\CLI\Baseline;
use Arkitect\CLI\CommandOutput;
use Arkitect\CLI\ConfigBuilder;
use Arkitect\CLI\Printer\Printer;
use Arkitect\CLI\Printer\PrinterFactory;
use Arkitect\CLI\Runner;
use Arkitect\CLI\TargetPhpVersion;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Check extends Command
{
    private const CONFIG_FILENAME_PARAM = 'config';
    private const TARGET_PHP_PARAM = 'target-php-version';
    private const STOP_ON_FAILURE_PARAM = 'stop-on-failure';
    private const USE_BASELINE_PARAM = 'use-baseline';
    private const SKIP_BASELINE_PARAM = 'skip-baseline';
    private const IGNORE_BASELINE_LINENUMBERS_PARAM = 'ignore-baseline-linenumbers';
    private const FORMAT_PARAM = 'format';
    private const AUTOLOAD_PARAM = 'autoload';

    private const GENERATE_BASELINE_PARAM = 'generate-baseline';
    private const DEFAULT_RULES_FILENAME = 'phparkitect.php';

    private const DEFAULT_BASELINE_FILENAME = 'phparkitect-baseline.json';

    private Autoloader $autoloader;

    public function __construct(?Autoloader $autoloader = null)
    {
        parent::__construct('check');

        $this->autoloader = $autoloader ?? new Autoloader();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Check that architectural rules are matched.')
            ->setHelp('This command allows you check that architectural rules defined in your config file are matched.')
            ->addOption(
                self::CONFIG_FILENAME_PARAM,
                'c',
                InputOption::VALUE_OPTIONAL,
                'File containing configs, such as rules to be matched',
                self::DEFAULT_RULES_FILENAME
            )
            ->addOption(
                self::TARGET_PHP_PARAM,
                't',
                InputOption::VALUE_OPTIONAL,
                'Target php version to use for parsing'
            )
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
                'Generate a file containing the current errors',
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
                self::IGNORE_BASELINE_LINENUMBERS_PARAM,
                'i',
                InputOption::VALUE_NONE,
                'Ignore line numbers when checking the baseline'
            )
            ->addOption(
                self::FORMAT_PARAM,
                'f',
                InputOption::VALUE_OPTIONAL,
                'Output format: text (default), json, gitlab',
                'text'
            )
            ->addOption(
                self::AUTOLOAD_PARAM,
                'a',
                InputOption::VALUE_REQUIRED,
                'Specify an autoload file to use',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // we write everything on STDERR apart from the list of violations which goes on STDOUT
        // this allows to pipe the output of this command to a file while showing output on the terminal
        $stdOut = $output;
        $output = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        $commandOutput = new CommandOutput($output);

        try {
            $verbose = (bool) $input->getOption('verbose');
            $rulesFilename = $input->getOption(self::CONFIG_FILENAME_PARAM);
            $stopOnFailure = (bool) $input->getOption(self::STOP_ON_FAILURE_PARAM);
            $useBaseline = (string) $input->getOption(self::USE_BASELINE_PARAM);
            $skipBaseline = (bool) $input->getOption(self::SKIP_BASELINE_PARAM);
            $ignoreBaselineLinenumbers = (bool) $input->getOption(self::IGNORE_BASELINE_LINENUMBERS_PARAM);
            $generateBaseline = $input->getOption(self::GENERATE_BASELINE_PARAM);
            $phpVersion = $input->getOption(self::TARGET_PHP_PARAM);
            $format = $input->getOption(self::FORMAT_PARAM);

            $commandOutput->printHeading($this->getApplication()?->getVersion());

            $config = ConfigBuilder::loadFromFile($rulesFilename)
                ->autoloadFilePath($input->getOption(self::AUTOLOAD_PARAM))
                ->stopOnFailure($stopOnFailure)
                ->targetPhpVersion(TargetPhpVersion::create($phpVersion))
                ->baselineFilePath(Baseline::resolveFilePath($useBaseline, self::DEFAULT_BASELINE_FILENAME))
                ->ignoreBaselineLinenumbers($ignoreBaselineLinenumbers)
                ->skipBaseline($skipBaseline)
                ->format($format);

            $this->autoloader->load($config->getAutoloadFilePath(), $output);
            $printer = $this->createPrinter($output, $config->getFormat());
            $progress = $commandOutput->createProgress($verbose);
            $baseline = $this->createBaseline($output, $config->isSkipBaseline(), $config->getBaselineFilePath());

            $output->writeln("Config file '$rulesFilename' found\n");

            $runner = new Runner();

            if (false !== $generateBaseline) {
                $result = $runner->baseline($config, $progress);

                $baselineFilePath = Baseline::save($generateBaseline, self::DEFAULT_BASELINE_FILENAME, $result->getViolations(), $ignoreBaselineLinenumbers);

                $output->writeln("ℹ️ Baseline file '$baselineFilePath' created!");

                return self::SUCCESS;
            }

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

            return $result->hasErrors() ? self::FAILURE : self::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln("❌ {$e->getMessage()}");

            return self::FAILURE;
        } finally {
            $commandOutput->printExecutionTime();
        }
    }

    private function createPrinter(OutputInterface $output, string $format): Printer
    {
        $output->writeln("Output format: $format");

        return PrinterFactory::create($format);
    }

    private function createBaseline(OutputInterface $output, bool $skipBaseline, ?string $baselineFilePath): Baseline
    {
        $baseline = Baseline::create($skipBaseline, $baselineFilePath);

        $baseline->getFilename() && $output->writeln("Baseline file '{$baseline->getFilename()}' found");

        return $baseline;
    }
}
