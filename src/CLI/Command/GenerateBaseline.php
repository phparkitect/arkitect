<?php

declare(strict_types=1);

namespace Arkitect\CLI\Command;

use Arkitect\CLI\BaselineFileRepository;
use Arkitect\CLI\GenerateBaselineHandler;
use Arkitect\CLI\GenerateBaselineOptions;
use Arkitect\CLI\Runner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateBaseline extends Command
{
    private const FILENAME_ARG = 'filename';

    private GenerateBaselineHandler $handler;

    private CommonOptions $commonOptions;

    private CommandRuntime $runtime;

    public function __construct(?GenerateBaselineHandler $handler = null)
    {
        // assigned before the parent constructor because Symfony's
        // Command::__construct() invokes configure(), which uses it
        $this->commonOptions = new CommonOptions();

        parent::__construct('generate-baseline');

        $this->handler = $handler ?? new GenerateBaselineHandler(new Runner(), new BaselineFileRepository());
        $this->runtime = new CommandRuntime();
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
                BaselineFileRepository::DEFAULT_FILENAME
            );

        $this->commonOptions->addTo($this);
        $this->commonOptions->addIgnoreBaselineLinenumbers($this);
    }

    protected function isRunningAsPhar(): bool
    {
        return '' !== \Phar::running();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->runtime->raiseLimits();
        $startTime = microtime(true);

        // we write everything on STDERR to be consistent with the check command
        $output = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        try {
            $verbose = (bool) $input->getOption('verbose');
            $options = $this->parseOptions($input);

            if ($this->isRunningAsPhar() && null === $options->getAutoloadFilePath()) {
                $output->writeln('❌ The --autoload option is required when running phparkitect as a PHAR');

                return self::FAILURE;
            }

            $this->runtime->printHeadingLine($this, $output);
            $this->commonOptions->requireAutoload($input, $output);

            $progress = $this->runtime->createProgress($output, $verbose);

            $this->handler->generateBaseline($options, $progress, $output);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln("❌ {$e->getMessage()}");

            return self::FAILURE;
        } finally {
            $this->runtime->printExecutionTime($output, $startTime);
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
}
