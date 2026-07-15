<?php

declare(strict_types=1);

namespace Arkitect\CLI\Command;

use Symfony\Component\Console\Input\InputOption;

/**
 * Single source of truth for the options shared by more than one command:
 * each command picks the ones it needs in its configure().
 */
final class CommonOptions
{
    public const CONFIG_FILENAME = 'config';
    public const TARGET_PHP_VERSION = 'target-php-version';
    public const IGNORE_BASELINE_LINENUMBERS = 'ignore-baseline-linenumbers';
    public const AUTOLOAD = 'autoload';

    private const DEFAULT_RULES_FILENAME = 'phparkitect.php';

    public static function config(): InputOption
    {
        return new InputOption(
            self::CONFIG_FILENAME,
            'c',
            InputOption::VALUE_OPTIONAL,
            'File containing configs, such as rules to be matched',
            self::DEFAULT_RULES_FILENAME
        );
    }

    public static function targetPhpVersion(): InputOption
    {
        return new InputOption(
            self::TARGET_PHP_VERSION,
            't',
            InputOption::VALUE_OPTIONAL,
            'Target php version to use for parsing'
        );
    }

    public static function ignoreBaselineLinenumbers(): InputOption
    {
        return new InputOption(
            self::IGNORE_BASELINE_LINENUMBERS,
            'i',
            InputOption::VALUE_NONE,
            'Ignore line numbers when checking or generating the baseline'
        );
    }

    public static function autoload(): InputOption
    {
        return new InputOption(
            self::AUTOLOAD,
            'a',
            InputOption::VALUE_REQUIRED,
            'Specify an autoload file to use'
        );
    }
}
