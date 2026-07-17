<?php

declare(strict_types=1);

namespace Arkitect\CLI\Command;

use Arkitect\CLI\Baseline;
use Arkitect\CLI\GenerateBaselineHandler;
use Arkitect\CLI\GenerateBaselineOptions;
use Arkitect\CLI\Progress\DebugProgress;
use Arkitect\CLI\Progress\Progress;
use Arkitect\CLI\Progress\ProgressBarProgress;
use Arkitect\CLI\Runner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateBaseline extends Command
{
    private const FILENAME_ARG = 'filename';

    private const SUCCESS_CODE = 0;
    private const ERROR_CODE = 1;

    private GenerateBaselineHandler $handler;

    private CommonOptions $commonOptions;

    public function __construct(?GenerateBaselineHandler $handler = null)
    {
        // assigned before the parent constructor because Symfony's
        // Command::__construct() invokes configure(), which uses it
        $this->commonOptions = new CommonOptions();

        parent::__construct('generate-baseline');

        $this->handler = $handler ?? new GenerateBaselineHandler(new Runner());
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generate a file containing the current violations, to be ignored by the check command.')
            ->setHelp('This command runs the analysis and saves the current violations to a baseline file, so that the check command can ignore them.')
            ->addArgument(
                self::FILENAME_ARG,
                InputArgument::OPTIONAL,
                'The baseline file to create',
                Baseline::DEFAULT_FILENAME
            );

        $this->commonOptions->addTo($this);
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

        // we write everything on STDERR to be consistent with the check command
        $output = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        try {
            $verbose = (bool) $input->getOption('verbose');
            $options = $this->parseOptions($input);

            if ($this->isRunningAsPhar() && null === $options->getAutoloadFilePath()) {
                $output->writeln('❌ The --autoload option is required when running phparkitect as a PHAR');

                return self::ERROR_CODE;
            }

            $this->printHeadingLine($output);
            $this->commonOptions->requireAutoload($input, $output);

            $progress = $this->createProgress($output, $verbose);

            $this->handler->generateBaseline($options, $progress, $output);

            return self::SUCCESS_CODE;
        } catch (\Throwable $e) {
            $output->writeln("❌ {$e->getMessage()}");

            return self::ERROR_CODE;
        } finally {
            $this->printExecutionTime($output, $startTime);
        }
    }

    protected function parseOptions(InputInterface $input): GenerateBaselineOptions
    {
        return new GenerateBaselineOptions(
            configFilePath: $this->commonOptions->configFilePath($input),
            targetPhpVersion: $this->commonOptions->targetPhpVersion($input),
            autoloadFilePath: $this->commonOptions->autoloadFilePath($input),
            ignoreBaselineLinenumbers: $this->commonOptions->isIgnoreBaselineLinenumbers($input),
            baselineFilePath: (string) $input->getArgument(self::FILENAME_ARG),
        );
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
