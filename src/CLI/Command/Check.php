<?php

declare(strict_types=1);

namespace Arkitect\CLI\Command;

use Arkitect\CLI\Config;
use Arkitect\CLI\Printer\Printer;
use Arkitect\CLI\Progress\DebugProgress;
use Arkitect\CLI\Progress\ProgressBarProgress;
use Arkitect\CLI\Runner;
use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Exceptions\FailOnFirstViolationException;
use Arkitect\Rules\ParsingErrors;
use Arkitect\Rules\Violations;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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

    private const GENERATE_BASELINE_PARAM = 'generate-baseline';
    private const DEFAULT_RULES_FILENAME = 'phparkitect.php';

    private const DEFAULT_BASELINE_FILENAME = 'phparkitect-baseline.json';

    private const SUCCESS_CODE = 0;
    private const ERROR_CODE = 1;

    public function __construct()
    {
        parent::__construct('check');
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
                'File containing configs, such as rules to be matched'
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
                'Output format: json or text (default)',
                'text'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '-1');
        ini_set('xdebug.max_nesting_level', '10000');
        $startTime = microtime(true);

        try {
            $verbose = (bool) $input->getOption('verbose');
            $stopOnFailure = (bool) $input->getOption(self::STOP_ON_FAILURE_PARAM);
            $useBaseline = (string) $input->getOption(self::USE_BASELINE_PARAM);
            $skipBaseline = (bool) $input->getOption(self::SKIP_BASELINE_PARAM);
            $ignoreBaselineLinenumbers = (bool) $input->getOption(self::IGNORE_BASELINE_LINENUMBERS_PARAM);
            $format = $input->getOption(self::FORMAT_PARAM);
            $onlyErrors = Printer::FORMAT_JSON === $format;

            if (true !== $skipBaseline && !$useBaseline && file_exists(self::DEFAULT_BASELINE_FILENAME)) {
                $useBaseline = self::DEFAULT_BASELINE_FILENAME;
            }

            if ($useBaseline && !file_exists($useBaseline)) {
                $output->writeln('<error>Baseline file not found.</error>');

                return self::ERROR_CODE;
            }

            if (!$onlyErrors) {
                $output->writeln('<info>Baseline found: '.$useBaseline.'</info>');
            }

            $generateBaseline = $input->getOption(self::GENERATE_BASELINE_PARAM);

            /** @var string|null $phpVersion */
            $phpVersion = $input->getOption('target-php-version');
            $targetPhpVersion = TargetPhpVersion::create($phpVersion);

            $progress = $verbose ? new DebugProgress($output) : new ProgressBarProgress($output);

            if (!$onlyErrors) {
                $this->printHeadingLine($output);
            }

            $rulesFilename = $this->getConfigFilename($input);
            if (!$onlyErrors) {
                $output->writeln(sprintf("Config file: %s\n", $rulesFilename));
            }

            $config = new Config();

            $this->readRules($config, $rulesFilename);

            $runner = new Runner($stopOnFailure);
            try {
                $runner->run($config, $progress, $targetPhpVersion, $onlyErrors);
            } catch (FailOnFirstViolationException $e) {
            }
            $violations = $runner->getViolations();
            $violations->sort();

            if (false !== $generateBaseline) {
                if (null === $generateBaseline) {
                    $generateBaseline = self::DEFAULT_BASELINE_FILENAME;
                }
                $this->saveBaseline($generateBaseline, $violations);

                $output->writeln('<info>Baseline file \''.$generateBaseline.'\'created!</info>');
                if (!$onlyErrors) {
                    $this->printExecutionTime($output, $startTime);
                }

                return self::SUCCESS_CODE;
            }

            if ($useBaseline) {
                $baseline = $this->loadBaseline($useBaseline);

                $violations->remove($baseline, $ignoreBaselineLinenumbers);
            }

            if ($violations->count() > 0) {
                $this->printViolations($violations, $output, $format, $onlyErrors);
                if (!$onlyErrors) {
                    $this->printExecutionTime($output, $startTime);
                }

                return self::ERROR_CODE;
            }

            $parsedErrors = $runner->getParsingErrors();
            if ($parsedErrors->count() > 0) {
                $this->printParsedErrors($parsedErrors, $output, $onlyErrors);
                $this->printExecutionTime($output, $startTime);

                return self::ERROR_CODE;
            }
        } catch (\Throwable $e) {
            $output->writeln($e->getMessage());
            $this->printExecutionTime($output, $startTime);

            return self::ERROR_CODE;
        }

        $this->printNoViolationsDetectedMessage($output, $onlyErrors);

        if (!$onlyErrors) {
            $this->printExecutionTime($output, $startTime);
        }

        return self::SUCCESS_CODE;
    }

    protected function readRules(Config $ruleChecker, string $rulesFilename): void
    {
        \Closure::fromCallable(function () use ($ruleChecker, $rulesFilename): ?bool {
            /** @psalm-suppress UnresolvableInclude $config */
            $config = require $rulesFilename;

            Assert::isCallable($config);

            return $config($ruleChecker);
        })();
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

        $output->writeln('<info>Execution time: '.$executionTime."s</info>\n");
    }

    private function loadBaseline(string $filename): Violations
    {
        return Violations::fromJson(file_get_contents($filename));
    }

    private function saveBaseline(string $filename, Violations $violations): void
    {
        file_put_contents($filename, json_encode($violations, \JSON_PRETTY_PRINT));
    }

    private function getConfigFilename(InputInterface $input): string
    {
        $filename = $input->getOption(self::CONFIG_FILENAME_PARAM);

        if (null === $filename) {
            $filename = self::DEFAULT_RULES_FILENAME;
        }

        Assert::file($filename, 'Config file not found');

        return $filename;
    }

    private function printViolations(Violations $violations, OutputInterface $output, string $format, bool $onlyErrors = false): void
    {
        if (!$onlyErrors) {
            $output->writeln('<error>ERRORS!</error>');
        }
        $output->writeln(sprintf('%s', $violations->toString($format)));
        if (!$onlyErrors) {
            $output->writeln(sprintf('<error>%s VIOLATIONS DETECTED!</error>', \count($violations)));
        }
    }

    private function printParsedErrors(ParsingErrors $parsingErrors, OutputInterface $output, bool $onlyErrors = false): void
    {
        if (!$onlyErrors) {
            $output->writeln('<error>ERROR ON PARSING THESE FILES:</error>');
        }
        $output->writeln(sprintf('%s', $parsingErrors->toString()));
    }

    private function printNoViolationsDetectedMessage(OutputInterface $output, bool $onlyErrors = false): void
    {
        if (!$onlyErrors) {
            $output->writeln('<info>NO VIOLATIONS DETECTED!</info>');
        }
    }
}
