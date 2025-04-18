<?php

declare(strict_types=1);

namespace Arkitect\CLI\Command;

use Arkitect\CLI\Baseline;
use Arkitect\CLI\Config;
use Arkitect\CLI\Printer\PrinterFactory;
use Arkitect\CLI\Progress\DebugProgress;
use Arkitect\CLI\Progress\ProgressBarProgress;
use Arkitect\CLI\Runner;
use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Rules\Violations;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

class Check extends Command
{
    private const CONFIG_FILENAME_PARAM = 'config';
    private const TARGET_PHP_PARAM = 'target-php-version';
    private const STOP_ON_FAILURE_PARAM = 'stop-on-failure';
    private const USE_BASELINE_PARAM = 'use-baseline';
    private const SKIP_BASELINE_PARAM = 'skip-baseline';
    private const IGNORE_BASELINE_LINENUMBERS_PARAM = 'ignore-baseline-linenumbers';
    private const FORMAT_PARAM = 'format';

    private const GENERATE_BASELINE_PARAM = 'generate-baseline';
    private const DEFAULT_RULES_FILENAME = 'phparkitect.php';

    private const DEFAULT_BASELINE_FILENAME = 'phparkitect-baseline.json';

    private const SUCCESS_CODE = 0;
    private const ERROR_CODE = 1;

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
                self::CONFIG_FILENAME_PARAM,
                'c',
                InputOption::VALUE_OPTIONAL,
                'File containing configs, such as rules to be matched'
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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '-1');
        ini_set('xdebug.max_nesting_level', '10000');
        $startTime = microtime(true);

        try {
            $verbose = (bool) $input->getOption('verbose');
            $stopOnFailure = (bool) $input->getOption(self::STOP_ON_FAILURE_PARAM);
            $useBaseline = (string) $input->getOption(self::USE_BASELINE_PARAM);
            $skipBaseline = (bool) $input->getOption(self::SKIP_BASELINE_PARAM);
            $ignoreBaselineLinenumbers = (bool) $input->getOption(self::IGNORE_BASELINE_LINENUMBERS_PARAM);
            $generateBaseline = $input->getOption(self::GENERATE_BASELINE_PARAM);
            $phpVersion = $input->getOption('target-php-version');
            $format = $input->getOption(self::FORMAT_PARAM);

            // we write everything on STDERR apart from the list of violations which goes on STDOUT
            // this allows to pipe the output of this command to a file while showing output on the terminal
            $stdOut = $output;
            $output = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

            $targetPhpVersion = TargetPhpVersion::create($phpVersion);

            $progress = $verbose ? new DebugProgress($output) : new ProgressBarProgress($output);

            $baseline = Baseline::create($skipBaseline, $useBaseline, self::DEFAULT_BASELINE_FILENAME);

            $printer = (new PrinterFactory())->create($format);

            $this->printHeadingLine($output);

            $baseline->getFilename() && $output->writeln("Baseline file '{$baseline->getFilename()}' found");

            $rulesFilename = $this->getConfigFilename($input);

            $output->writeln("Config file '$rulesFilename' found\n");

            $config = new Config();

            $this->readRules($config, $rulesFilename);

            $runner = new Runner($stopOnFailure);
            $result = $runner->run($config, $progress, $targetPhpVersion);

            $violations = $result->getViolations();

            if (false !== $generateBaseline) {
                $baselineFilePath = Baseline::save($generateBaseline, self::DEFAULT_BASELINE_FILENAME, $violations);

                $output->writeln("ℹ️ Baseline file '$baselineFilePath' created!");

                return self::SUCCESS_CODE;
            }

            $baseline->applyTo($violations, $ignoreBaselineLinenumbers);

            // we always print this so we do not have to do additional ifs later
            $stdOut->writeln($printer->print($violations->groupedByFqcn()));

            if ($violations->count() > 0) {
                $output->writeln("⚠️ {$violations->count()} violations detected!");
            }

            if ($result->hasParsingErrors()) {
                $output->writeln('❌ could not parse these files:');
                $output->writeln($result->getParsingErrors()->toString());
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

    protected function readRules(Config $ruleChecker, string $rulesFilename): void
    {
        \Closure::fromCallable(function () use ($ruleChecker, $rulesFilename): ?bool {
            /** @psalm-suppress UnresolvableInclude $config */
            $config = require $rulesFilename;

            Assert::isCallable($config);

            return $config($ruleChecker);
        })();
    }

    protected function printHeadingLine(OutputInterface $output): void
    {
        $app = $this->getApplication();

        $version = $app ? $app->getVersion() : 'unknown';

        $output->writeln("<info>PHPArkitect $version</info>\n");
    }

    protected function printExecutionTime(OutputInterface $output, float $startTime): void
    {
        $endTime = microtime(true);
        $executionTime = number_format($endTime - $startTime, 2);

        $output->writeln("⏱️ Execution time: $executionTime\n");
    }

    private function getConfigFilename(InputInterface $input): string
    {
        $filename = $input->getOption(self::CONFIG_FILENAME_PARAM);

        if (null === $filename) {
            $filename = self::DEFAULT_RULES_FILENAME;
        }

        Assert::file($filename, "Config file '$filename' not found");

        return $filename;
    }
}
