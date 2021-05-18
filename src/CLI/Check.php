<?php
declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Rules\Violations;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

class Check extends Command
{
    private const CONFIG_FILENAME_PARAM = 'config';

    private const DEFAULT_FILENAME = 'phparkitect.php';

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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '-1');

        try {
            $verbose = $input->getOption('verbose');

            $progress = $verbose ? new DebugProgress($output) : new ProgressBarProgress($output);

            $this->printHeadingLine($output);

            $rulesFilename = $this->getConfigFilename($input);
            $output->writeln(sprintf("Config file: %s\n", $rulesFilename));

            $config = new Config();

            $this->readRules($config, $rulesFilename);

            $runner = new Runner();
            $violations = $runner->run($config, $progress);

            if ($violations->count() > 0) {
                $this->printViolations($violations, $output);

                exit(self::ERROR_CODE);
            }
        } catch (\Throwable $e) {
            $output->writeln($e->getMessage());
            exit(self::ERROR_CODE);
        }

        exit(self::SUCCESS_CODE);
    }

    protected function readRules(Config $ruleChecker, string $rulesFilename): void
    {
        \Closure::fromCallable(function () use ($ruleChecker, $rulesFilename) {
            $config = require $rulesFilename;

            Assert::isCallable($config);

            return $config($ruleChecker);
        })();
    }

    protected function printSummaryLine(OutputInterface $output, int $assertionCount, int $violationCount): void
    {
        $output->writeln(
            sprintf(
                "\nAssertions: %d, Violations: %d.\n",
                $assertionCount,
                $violationCount
            )
        );
    }

    protected function printHeadingLine(OutputInterface $output): void
    {
        $version = Version::get();
        $output->writeln("<info>PHPArkitect $version</info>\n");
    }

    private function getConfigFilename(InputInterface $input)
    {
        $filename = $input->getOption(self::CONFIG_FILENAME_PARAM);

        if (null === $filename) {
            $filename = self::DEFAULT_FILENAME;
        }

        Assert::file($filename, 'Config file not found');

        return $filename;
    }

    private function printViolations(Violations $violations, OutputInterface $output): void
    {
        $output->writeln('<error>ERRORS!</error>');
        $output->writeln(sprintf('%s', $violations->toString()));
    }
}
