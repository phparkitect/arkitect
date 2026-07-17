<?php

declare(strict_types=1);

namespace Arkitect\CLI\Command;

use Arkitect\CLI\BaselineFileRepository;
use Arkitect\CLI\PruneBaselineHandler;
use Arkitect\CLI\PruneBaselineOptions;
use Arkitect\CLI\Runner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PruneBaseline extends Command
{
    private const FILENAME_ARG = 'filename';

    private PruneBaselineHandler $handler;

    private CommonOptions $commonOptions;

    private CommandRuntime $runtime;

    public function __construct(?PruneBaselineHandler $handler = null)
    {
        // assigned before the parent constructor because Symfony's
        // Command::__construct() invokes configure(), which uses it
        $this->commonOptions = new CommonOptions();

        parent::__construct('prune-baseline');

        $this->handler = $handler ?? new PruneBaselineHandler(new Runner(), new BaselineFileRepository());
        $this->runtime = new CommandRuntime();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Remove from the baseline the violations that no longer exist, without adding anything.')
            ->setHelp('This command runs the analysis and keeps in the baseline only the entries that still match a current violation. It never adds entries, so unlike regenerating the baseline it cannot hide new violations, and it refreshes stale line numbers.')
            ->addArgument(
                self::FILENAME_ARG,
                InputArgument::OPTIONAL,
                'The baseline file to prune',
                BaselineFileRepository::DEFAULT_FILENAME
            );

        $this->commonOptions->addTo($this);
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

            $this->handler->pruneBaseline($options, $progress, $output);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln("❌ {$e->getMessage()}");

            return self::FAILURE;
        } finally {
            $this->runtime->printExecutionTime($output, $startTime);
        }
    }

    protected function parseOptions(InputInterface $input): PruneBaselineOptions
    {
        return new PruneBaselineOptions(
            configFilePath: $this->commonOptions->configFilePath($input),
            targetPhpVersion: $this->commonOptions->targetPhpVersion($input),
            autoloadFilePath: $this->commonOptions->autoloadFilePath($input),
            ignoreBaselineLinenumbers: $this->commonOptions->isIgnoreBaselineLinenumbers($input),
            baselineFilePath: (string) $input->getArgument(self::FILENAME_ARG),
        );
    }
}
