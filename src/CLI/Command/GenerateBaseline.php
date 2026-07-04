<?php

declare(strict_types=1);

namespace Arkitect\CLI\Command;

use Arkitect\CLI\Baseline;
use Arkitect\CLI\ConfigBuilder;
use Arkitect\CLI\Runner;
use Arkitect\CLI\TargetPhpVersion;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateBaseline extends AbstractCommand
{
    private const FILENAME_ARGUMENT = 'filename';

    public function __construct()
    {
        parent::__construct('generate-baseline');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generate a baseline file containing the current violations.')
            ->setHelp(
                'This command runs the checks defined in your config file and saves the current violations '
                .'to a baseline file, so they can be ignored in subsequent <comment>check</comment> runs.'
            )
            ->addUsage('generates '.self::DEFAULT_BASELINE_FILENAME.' in the current dir')
            ->addUsage('my-baseline.json generates my-baseline.json in the current dir')
            ->addArgument(
                self::FILENAME_ARGUMENT,
                InputArgument::OPTIONAL,
                'The baseline file to generate (default: '.self::DEFAULT_BASELINE_FILENAME.')'
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
            $ignoreBaselineLinenumbers = (bool) $input->getOption(self::IGNORE_BASELINE_LINENUMBERS_PARAM);
            $phpVersion = $input->getOption(self::TARGET_PHP_PARAM);
            /** @var string|null $baselineFilename */
            $baselineFilename = $input->getArgument(self::FILENAME_ARGUMENT);

            $output = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

            if ($this->isRunningAsPhar() && null === $input->getOption(self::AUTOLOAD_PARAM)) {
                $output->writeln('❌ The --autoload option is required when running phparkitect as a PHAR');

                return self::ERROR_CODE;
            }

            $this->printHeadingLine($output);

            $config = ConfigBuilder::loadFromFile($rulesFilename)
                ->autoloadFilePath($input->getOption(self::AUTOLOAD_PARAM))
                ->targetPhpVersion(TargetPhpVersion::create($phpVersion));

            $this->requireAutoload($output, $config->getAutoloadFilePath());
            $progress = $this->createProgress($output, $verbose);

            $output->writeln("Config file '$rulesFilename' found\n");

            $runner = new Runner();

            $result = $runner->baseline($config, $progress);

            $baselineFilePath = Baseline::save($baselineFilename, self::DEFAULT_BASELINE_FILENAME, $result->getViolations(), $ignoreBaselineLinenumbers);

            $output->writeln("ℹ️ Baseline file '$baselineFilePath' created!");

            return self::SUCCESS_CODE;
        } catch (\Throwable $e) {
            $output->writeln("❌ {$e->getMessage()}");

            return self::ERROR_CODE;
        } finally {
            $this->printExecutionTime($output, $startTime);
        }
    }
}
