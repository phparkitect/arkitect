<?php

declare(strict_types=1);

namespace Arkitect\CLI\Command;

use Arkitect\CLI\Progress\DebugProgress;
use Arkitect\CLI\Progress\Progress;
use Arkitect\CLI\Progress\ProgressBarProgress;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

abstract class AbstractCommand extends Command
{
    protected const CONFIG_FILENAME_PARAM = 'config';
    protected const TARGET_PHP_PARAM = 'target-php-version';
    protected const IGNORE_BASELINE_LINENUMBERS_PARAM = 'ignore-baseline-linenumbers';
    protected const AUTOLOAD_PARAM = 'autoload';

    protected const DEFAULT_RULES_FILENAME = 'phparkitect.php';
    protected const DEFAULT_BASELINE_FILENAME = 'phparkitect-baseline.json';

    protected const SUCCESS_CODE = 0;
    protected const ERROR_CODE = 1;

    protected function configureCommonOptions(): void
    {
        $this
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
                self::IGNORE_BASELINE_LINENUMBERS_PARAM,
                'i',
                InputOption::VALUE_NONE,
                'Ignore line numbers when checking or generating the baseline'
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
