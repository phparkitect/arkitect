<?php

declare(strict_types=1);

namespace Arkitect\CLI\Command;

use Arkitect\CLI\BaselineFileRepository;
use Arkitect\CLI\CheckHandler;
use Arkitect\CLI\CheckOptions;
use Arkitect\CLI\Runner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Check extends Command
{
    private const STOP_ON_FAILURE_PARAM = 'stop-on-failure';
    private const USE_BASELINE_PARAM = 'use-baseline';
    private const SKIP_BASELINE_PARAM = 'skip-baseline';
    private const FORMAT_PARAM = 'format';

    private const GENERATE_BASELINE_PARAM = 'generate-baseline';

    private CheckHandler $handler;

    private CommonOptions $commonOptions;

    private CommandRuntime $runtime;

    public function __construct(?CheckHandler $handler = null)
    {
        // assigned before the parent constructor because Symfony's
        // Command::__construct() invokes configure(), which uses it
        $this->commonOptions = new CommonOptions();

        parent::__construct('check');

        $this->handler = $handler ?? new CheckHandler(new Runner(), new BaselineFileRepository());
        $this->runtime = new CommandRuntime();
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
                '[MOVED] Use the generate-baseline command instead',
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

        // we write everything on STDERR apart from the list of violations which goes on STDOUT
        // this allows to pipe the output of this command to a file while showing output on the terminal
        $stdOut = $output;
        $output = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        try {
            // the option is kept (instead of being removed) so users get this
            // explanation rather than a generic "option does not exist" error
            $generateBaseline = $input->getOption(self::GENERATE_BASELINE_PARAM);
            if (false !== $generateBaseline) {
                $filename = \is_string($generateBaseline) ? " $generateBaseline" : '';
                $output->writeln('❌ The --generate-baseline option has been moved to its own command.');
                $output->writeln("   Run: phparkitect generate-baseline$filename");

                return self::FAILURE;
            }

            $verbose = (bool) $input->getOption('verbose');
            $options = $this->parseOptions($input);

            if ($this->isRunningAsPhar() && null === $options->getAutoloadFilePath()) {
                $output->writeln('❌ The --autoload option is required when running phparkitect as a PHAR');

                return self::FAILURE;
            }

            $this->runtime->printHeadingLine($this, $output);
            $this->commonOptions->requireAutoload($input, $output);

            $output->writeln("Output format: {$options->getFormat()}");
            $progress = $this->runtime->createProgress($output, $verbose);

            $result = $this->handler->check($options, $progress, $output, $stdOut);

            return $result->hasErrors() ? self::FAILURE : self::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln("❌ {$e->getMessage()}");

            return self::FAILURE;
        } finally {
            $this->runtime->printExecutionTime($output, $startTime);
        }
    }

    protected function parseOptions(InputInterface $input): CheckOptions
    {
        return new CheckOptions(
            configFilePath: $this->commonOptions->configFilePath($input),
            targetPhpVersion: $this->commonOptions->targetPhpVersion($input),
            stopOnFailure: (bool) $input->getOption(self::STOP_ON_FAILURE_PARAM),
            baselineFilePath: $this->resolveBaselineFilePath($input),
            ignoreBaselineLinenumbers: $this->commonOptions->isIgnoreBaselineLinenumbers($input),
            format: (string) $input->getOption(self::FORMAT_PARAM),
            autoloadFilePath: $this->commonOptions->autoloadFilePath($input),
        );
    }

    /**
     * The baseline file check should use, or null when there is nothing to
     * ignore: --skip-baseline opts out, and the default baseline is used only
     * when it happens to exist. An explicit --use-baseline is returned as is
     * even if missing, so that a wrong path fails loudly at load time instead
     * of being silently ignored.
     */
    private function resolveBaselineFilePath(InputInterface $input): ?string
    {
        if ((bool) $input->getOption(self::SKIP_BASELINE_PARAM)) {
            return null;
        }

        $useBaseline = (string) $input->getOption(self::USE_BASELINE_PARAM);

        if ('' !== $useBaseline) {
            return $useBaseline;
        }

        return file_exists(BaselineFileRepository::DEFAULT_FILENAME) ? BaselineFileRepository::DEFAULT_FILENAME : null;
    }
}
