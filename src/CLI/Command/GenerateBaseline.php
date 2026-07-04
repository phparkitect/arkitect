<?php

declare(strict_types=1);

namespace Arkitect\CLI\Command;

use Arkitect\CLI\Autoloader;
use Arkitect\CLI\Baseline;
use Arkitect\CLI\CommandOutput;
use Arkitect\CLI\ConfigBuilder;
use Arkitect\CLI\Runner;
use Arkitect\CLI\TargetPhpVersion;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateBaseline extends Command
{
    private const FILENAME_ARGUMENT = 'filename';

    /** @var \Closure(): bool */
    private \Closure $isRunningAsPhar;

    /**
     * @param \Closure(): bool|null $isRunningAsPhar
     */
    public function __construct(?\Closure $isRunningAsPhar = null)
    {
        parent::__construct('generate-baseline');

        $this->isRunningAsPhar = $isRunningAsPhar ?? static fn (): bool => '' !== \Phar::running();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generate a baseline file containing the current violations.')
            ->setHelp(
                'This command runs the checks defined in your config file and saves the current violations '
                .'to a baseline file, so they can be ignored in subsequent <comment>check</comment> runs.'
            )
            ->addUsage('generates '.Baseline::DEFAULT_FILENAME.' in the current dir')
            ->addUsage('my-baseline.json generates my-baseline.json in the current dir')
            ->addArgument(
                self::FILENAME_ARGUMENT,
                InputArgument::OPTIONAL,
                'The baseline file to generate (default: '.Baseline::DEFAULT_FILENAME.')'
            );

        $this->getDefinition()->addOptions([
            CommonOptions::config(),
            CommonOptions::targetPhpVersion(),
            CommonOptions::ignoreBaselineLinenumbers(),
            CommonOptions::autoload(),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '-1');
        ini_set('xdebug.max_nesting_level', '10000');
        $startTime = microtime(true);

        $output = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        $commandOutput = new CommandOutput($output);

        try {
            $verbose = (bool) $input->getOption('verbose');
            $rulesFilename = $input->getOption(CommonOptions::CONFIG_FILENAME);
            $ignoreBaselineLinenumbers = (bool) $input->getOption(CommonOptions::IGNORE_BASELINE_LINENUMBERS);
            $phpVersion = $input->getOption(CommonOptions::TARGET_PHP_VERSION);
            /** @var string|null $baselineFilename */
            $baselineFilename = $input->getArgument(self::FILENAME_ARGUMENT);

            if (($this->isRunningAsPhar)() && null === $input->getOption(CommonOptions::AUTOLOAD)) {
                $output->writeln('❌ The --autoload option is required when running phparkitect as a PHAR');

                return self::FAILURE;
            }

            $commandOutput->printHeading($this->getApplication()?->getVersion() ?? 'unknown');

            $config = ConfigBuilder::loadFromFile($rulesFilename)
                ->autoloadFilePath($input->getOption(CommonOptions::AUTOLOAD))
                ->targetPhpVersion(TargetPhpVersion::create($phpVersion));

            Autoloader::load($config->getAutoloadFilePath(), $output);
            $progress = $commandOutput->createProgress($verbose);

            $output->writeln("Config file '$rulesFilename' found\n");

            $runner = new Runner();

            $result = $runner->baseline($config, $progress);

            $baselineFilePath = Baseline::save($baselineFilename, $result->getViolations(), $ignoreBaselineLinenumbers);

            $output->writeln("ℹ️ Baseline file '$baselineFilePath' created!");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln("❌ {$e->getMessage()}");

            return self::FAILURE;
        } finally {
            $commandOutput->printExecutionTime($startTime);
        }
    }
}
