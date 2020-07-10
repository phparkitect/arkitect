<?php
declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\ArchViolationsException;
use Arkitect\RuleChecker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

class Check extends Command
{
    public function __construct()
    {
        parent::__construct('check');
    }

    protected function configure()
    {
        $this
            ->setDescription('Check that architectural rules are matched.')
            ->setHelp('This command allows you check that architectural rules defined in your config file are matched.')
            ->addOption('rules', 'r', InputOption::VALUE_REQUIRED, 'File containing rules to be matched');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rules = $input->getOption('rules');

        Assert::notNull($rules, 'You must specify the file containing rules');
        Assert::file($rules);

        $output->writeln("PHPArkitect 0.0.1\n");
        $output->writeln(sprintf("Rules file: %s\n", $rules));

        try {
            $errors = 0;

            require_once $rules;

            RuleChecker::run();
        } catch (ArchViolationsException $exception) {
            foreach ($exception->violations() as $violation) {
                $output->writeln(sprintf('<error>%s</error>', $violation));
            }

            $errors = $exception->violations()->count();
        }

        $output->writeln('');

        if ($errors) {
            $output->writeln('ERRORS!');
        }

        $output->writeln(
            sprintf(
                "Assertions: %d, Errors: %d.\n",
                RuleChecker::assertionsCount(),
                $errors
            )
        );

        return $errors;
    }
}
