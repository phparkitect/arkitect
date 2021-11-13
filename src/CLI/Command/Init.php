<?php
declare(strict_types=1);

namespace Arkitect\CLI\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

class Init extends Command
{
    public static $defaultName = 'init';

    public static $defaultDescription = <<< EOT
Creates a new phparkitect.php file
EOT;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("");

        try {

            $sourcePath = __DIR__ . '/../../../phparkitect-stub.php';
            $destPath = 'phparkitect.php';

            if (file_exists($destPath)) {
                $output->writeln("<info>File</info> phparkitect.php <info>found in current directory, nothing to do</info>");
                $output->writeln("<info>You are good to go, customize it and run with </info>php bin/phparkitect check");

                return 0;
            }

            $output->write("<info>Creating phparkitect.php file...</info>");

            Assert::file($sourcePath);

            copy($sourcePath, 'phparkitect.php');

            $output->writeln("<info> done</info>");
            $output->writeln("<info>customize it and run with </info>php bin/phparkitect check");

        } catch (\Throwable $e) {
            $output->writeln("");
            $output->writeln("<error>Ops, something went wrong: </error>");
            $output->writeln("<error>{$e->getMessage()}</error>");

            return -1;
        }

        return 0;
    }
}
