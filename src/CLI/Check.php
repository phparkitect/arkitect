<?php
declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Validation\Notification;
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

    protected function configure(): void
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

        $notifications = $ruleChecker->run();

        $violations = array_reduce(
            $notifications,
            function (int $count, Notification $notification) {
                return $count + $notification->getErrorCount();
            },
            0
        );

        if ($violations > 0) {
            $this->printViolations($notifications, $output);
        }

//        $this->printSummaryLine($output, $ruleChecker->ruleCount(), $notifications->count());

        return $violations;
    }

    protected function readRules(RuleChecker $ruleChecker, string $rulesFilename): void
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
        $output->writeln("<info>PHPArkitect 0.0.1</info>\n");
    }

    private function getConfigFilename(InputInterface $input)
    {
        $filename = $input->getOption(self::CONFIG_FILENAME_PARAM);

        Assert::notNull($filename, 'You must specify the file containing rules');
        Assert::file($filename, 'Config file not found');

        return $filename;
    }

    /**
     * @param Notification[] $notifications
     */
    private function printViolations(array $notifications, OutputInterface $output): void
    {
        $output->writeln('<error>ERRORS!</error>');

        foreach ($notifications as $notification) {
            $output->writeln(sprintf('%s', $notification));
        }
    }
}
