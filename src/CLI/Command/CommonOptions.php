<?php

declare(strict_types=1);

namespace Arkitect\CLI\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

/**
 * The CLI options shared by every command that runs an analysis.
 * Commands compose this object so the option names, shortcuts, defaults
 * and parsing stay in sync across the whole CLI.
 */
final class CommonOptions
{
    public const CONFIG_PARAM = 'config';
    public const TARGET_PHP_PARAM = 'target-php-version';
    public const AUTOLOAD_PARAM = 'autoload';
    public const IGNORE_BASELINE_LINENUMBERS_PARAM = 'ignore-baseline-linenumbers';

    private const DEFAULT_RULES_FILENAME = 'phparkitect.php';

    public function addTo(Command $command): void
    {
        $command
            ->addOption(
                self::CONFIG_PARAM,
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
                self::AUTOLOAD_PARAM,
                'a',
                InputOption::VALUE_REQUIRED,
                'Specify an autoload file to use',
            )
            ->addOption(
                self::IGNORE_BASELINE_LINENUMBERS_PARAM,
                'i',
                InputOption::VALUE_NONE,
                'Ignore line numbers when checking the baseline'
            );
    }

    public function configFilePath(InputInterface $input): string
    {
        return (string) $input->getOption(self::CONFIG_PARAM);
    }

    public function targetPhpVersion(InputInterface $input): ?string
    {
        $targetPhpVersion = $input->getOption(self::TARGET_PHP_PARAM);

        return \is_string($targetPhpVersion) ? $targetPhpVersion : null;
    }

    public function autoloadFilePath(InputInterface $input): ?string
    {
        $autoloadFilePath = $input->getOption(self::AUTOLOAD_PARAM);

        return \is_string($autoloadFilePath) ? $autoloadFilePath : null;
    }

    public function isIgnoreBaselineLinenumbers(InputInterface $input): bool
    {
        return (bool) $input->getOption(self::IGNORE_BASELINE_LINENUMBERS_PARAM);
    }

    /**
     * Loading the autoload file is a process-wide side effect, so it belongs
     * to the command layer: it is kept here, next to the option it consumes,
     * and out of the unit-testable handlers.
     *
     * @psalm-suppress UnresolvableInclude
     */
    public function requireAutoload(InputInterface $input, OutputInterface $output): void
    {
        $filePath = $this->autoloadFilePath($input);

        if (null === $filePath) {
            return;
        }

        Assert::file($filePath, "Cannot find '$filePath'");

        require_once $filePath;

        $output->writeln("Autoload file '$filePath' added");
    }
}
