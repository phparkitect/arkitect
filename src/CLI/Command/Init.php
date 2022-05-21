<?php
declare(strict_types=1);

namespace Arkitect\CLI\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

class Init extends Command
{
    /** @var string|null */
    public static $defaultName = 'init';

    /** @var string|null */
    public static $defaultDescription = <<< 'EOT'
Creates a new phparkitect.php file
EOT;

    /** @var string */
    public static $help = <<< 'EOT'
This command creates a new phparkitect.php in the current directory
You can customize the directory where the file is created specifying <comment>-d /dest/path</comment>
EOT;

    protected function configure(): void
    {
        $this
            ->addUsage('creates a phparkitect.php file in the current dir')
            ->addUsage('--dest-dir=/path/to/dir creates a phparkitect.php file in /path/to/dir')
            ->addUsage('-d /path/to/dir creates a phparkitect.php file in /path/to/dir')
            ->setHelp(self::$help)
            ->addOption(
                'dest-dir',
                'd',
                InputOption::VALUE_REQUIRED,
                'destination directory for the file',
                '.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('');

        try {
            $sourceFilePath = __DIR__.'/../../../phparkitect-stub.php';
            /** @psalm-suppress PossiblyInvalidCast $destPath */
            $destPath = (string) $input->getOption('dest-dir');
            $destFilePath = "$destPath/phparkitect.php";

            if (file_exists($destFilePath)) {
                $output->writeln('<info>File</info> phparkitect.php <info>found in current directory, nothing to do</info>');
                $output->writeln('<info>You are good to go, customize it and run with </info>php bin/phparkitect check');

                return 0;
            }

            if (!is_writable($destPath)) {
                $output->writeln("<error>Ops, it seems I cannot create the file in {$destPath}</error>");
                $output->writeln('Please check the directory is writable');

                return -1;
            }

            $output->write('<info>Creating phparkitect.php file...</info>');

            Assert::file($sourceFilePath);

            copy($sourceFilePath, $destFilePath);

            $output->writeln('<info> done</info>');
            $output->writeln('<info>customize it and run with </info>php bin/phparkitect check');
        } catch (\Throwable $e) {
            $output->writeln('');
            $output->writeln('<error>Ops, something went wrong: </error>');
            $output->writeln("<error>{$e->getMessage()}</error>");

            return -1;
        }

        return 0;
    }
}
