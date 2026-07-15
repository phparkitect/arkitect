<?php

declare(strict_types=1);

namespace Arkitect\CLI\Command;

use Arkitect\CLI\Baseline;
use Arkitect\CLI\CheckHandler;
use Arkitect\CLI\CheckOptions;
use Arkitect\CLI\Progress\DebugProgress;
use Arkitect\CLI\Progress\Progress;
use Arkitect\CLI\Progress\ProgressBarProgress;
use Arkitect\CLI\Runner;
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
    private const AUTOLOAD_PARAM = 'autoload';

    private const GENERATE_BASELINE_PARAM = 'generate-baseline';
    private const DEFAULT_RULES_FILENAME = 'phparkitect.php';

    private const SUCCESS_CODE = 0;
    private const ERROR_CODE = 1;

    private CheckHandler $handler;

    public function __construct(?CheckHandler $handler = null)
    {
        parent::__construct('check');

        $this->handler = $handler ?? new CheckHandler(new Runner());
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

    protected function isRunningAsPhar(): bool
    {
        return '' !== \Phar::running();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '-1');
        ini_set('xdebug.max_nesting_level', '10000');
        $startTime = microtime(true);

        // we write everything on STDERR apart from the list of violations which goes on STDOUT
        // this allows to pipe the output of this command to a file while showing output on the terminal
        $stdOut = $output;
        $output = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        try {
            $verbose = (bool) $input->getOption('verbose');
            $options = $this->parseOptions($input);

            if ($this->isRunningAsPhar() && null === $options->getAutoloadFilePath()) {
                $output->writeln('❌ The --autoload option is required when running phparkitect as a PHAR');

                return self::ERROR_CODE;
            }

            $this->printHeadingLine($output);
            $this->requireAutoload($output, $options->getAutoloadFilePath());

            $output->writeln("Output format: {$options->getFormat()}");
            $progress = $this->createProgress($output, $verbose);

            if ($options->shouldGenerateBaseline()) {
                $this->handler->generateBaseline($options, $progress, $output);

                return self::SUCCESS_CODE;
            }

            $result = $this->handler->check($options, $progress, $output, $stdOut);

            return $result->hasErrors() ? self::ERROR_CODE : self::SUCCESS_CODE;
        } catch (\Throwable $e) {
            $output->writeln("❌ {$e->getMessage()}");

            return self::ERROR_CODE;
        } finally {
            $this->printExecutionTime($output, $startTime);
        }
    }

    protected function parseOptions(InputInterface $input): CheckOptions
    {
        $targetPhpVersion = $input->getOption(self::TARGET_PHP_PARAM);
        $autoloadFilePath = $input->getOption(self::AUTOLOAD_PARAM);
        $useBaseline = (string) $input->getOption(self::USE_BASELINE_PARAM);

        // false = option not set, null = option set but without value, string = option with value
        $generateBaseline = $input->getOption(self::GENERATE_BASELINE_PARAM);

        return new CheckOptions(
            configFilePath: (string) $input->getOption(self::CONFIG_FILENAME_PARAM),
            targetPhpVersion: \is_string($targetPhpVersion) ? $targetPhpVersion : null,
            stopOnFailure: (bool) $input->getOption(self::STOP_ON_FAILURE_PARAM),
            baselineFilePath: Baseline::resolveFilePath($useBaseline, Baseline::DEFAULT_FILENAME),
            skipBaseline: (bool) $input->getOption(self::SKIP_BASELINE_PARAM),
            ignoreBaselineLinenumbers: (bool) $input->getOption(self::IGNORE_BASELINE_LINENUMBERS_PARAM),
            generateBaseline: false !== $generateBaseline,
            generateBaselineFilePath: \is_string($generateBaseline) ? $generateBaseline : null,
            format: (string) $input->getOption(self::FORMAT_PARAM),
            autoloadFilePath: \is_string($autoloadFilePath) ? $autoloadFilePath : null,
        );
    }

    /**
     * @psalm-suppress UnresolvableInclude
     */
    protected function requireAutoload(OutputInterface $output, ?string $filePath): void
    {
        if (null === $filePath) {
            return;
        }

        Assert::file($filePath, "Cannot find '$filePath'");

        require_once $filePath;

        $output->writeln("Autoload file '$filePath' added");
    }

    protected function createProgress(OutputInterface $output, bool $verbose): Progress
    {
        $output->writeln('Progress: '.($verbose ? 'debug' : 'bar'));

        return $verbose ? new DebugProgress($output) : new ProgressBarProgress($output);
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
}
