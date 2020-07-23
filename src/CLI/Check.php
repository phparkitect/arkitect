<?php
declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\RuleChecker;
use Arkitect\Rules\Violations;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

class Check extends Command
{
    public const FAILURE = 1;
    private const CONFIG_FILENAME_PARAM = 'config';

    public function __construct()
    {
        parent::__construct('check');
    }

    protected function configure()
    {
        $this
            ->setDescription('Check that architectural rules are matched.')
            ->setHelp('This command allows you check that architectural rules defined in your config file are matched.')
            ->addOption(
                self::CONFIG_FILENAME_PARAM,
                'c',
                InputOption::VALUE_REQUIRED,
                'File containing configs, such as rules to be matched'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->printHeadingLine($output);

        $rulesFilename = $this->getConfigFilename($input);
        $output->writeln(sprintf("Config file: %s\n", $rulesFilename));

        $ruleChecker = new RuleChecker();

        $this->readRules($ruleChecker, $rulesFilename);

        $violations = $ruleChecker->run();

        if ($violations->count() > 0) {
            $this->printViolations($violations, $output);
        }

        $this->printSummaryLine($output, $violations->count());

        return $violations->count();
    }

    protected function readRules(RuleChecker $ruleChecker, string $rulesFilename): void
    {
        \Closure::fromCallable(function () use ($ruleChecker, $rulesFilename) {
            $config = require $rulesFilename;

            Assert::isCallable($config);

            return $config($ruleChecker);
        })();
    }

    protected function printSummaryLine(OutputInterface $output, int $violationCount): void
    {
        $output->writeln(
            sprintf(
                "\nAssertions: %d, Violations: %d.\n",
                RuleChecker::assertionsCount(),
                $violationCount
            )
        );
    }

    protected function printHeadingLine(OutputInterface $output): void
    {
        $output->writeln("<info>PHPArkitect 0.0.1</info>\n");
    }

    private function getConfigFilename(InputInterface $input)
    {
        $filename = $input->getOption(self::CONFIG_FILENAME_PARAM);

        Assert::notNull($filename, 'You must specify the file containing rules');
        Assert::file($filename, 'Config file not found');

        return $filename;
    }

    private function printViolations(Violations $violations, OutputInterface $output): void
    {
        $output->writeln('<error>ERRORS!</error>');

        foreach ($violations as $violation) {
            $output->writeln(sprintf('%s', $violation));
        }
    }
}
