<?php
declare(strict_types=1);

namespace Arkitect\CLI\Command;

use Arkitect\Analyzer\FileParserFactory;
use Arkitect\ClassSet;
use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Rules\Violations;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DebugExpression extends Command
{
    /** @var string|null */
    public static $defaultName = 'debug:expression';

    /** @var string|null */
    public static $defaultDescription = <<< 'EOT'
Check which classes respect an expression
EOT;

    /** @var string */
    public static $help = <<< 'EOT'
Check which classes respect an expression
EOT;

    protected function configure(): void
    {
        $this
            ->setHelp(self::$help)
            ->addArgument('expression', InputArgument::REQUIRED)
            ->addArgument('arguments', InputArgument::IS_ARRAY)
            ->addOption(
                'from-dir',
                'd',
                InputOption::VALUE_REQUIRED,
                'The folder in which to search the classes',
                '.'
            )
            ->addOption(
                'target-php-version',
                't',
                InputOption::VALUE_OPTIONAL,
                'Target php version to use for parsing'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $phpVersion = $input->getOption('target-php-version');
        $targetPhpVersion = TargetPhpVersion::create($phpVersion);
        $fileParser = FileParserFactory::createFileParser($targetPhpVersion);

        $classSet = ClassSet::fromDir($input->getOption('from-dir'));
        foreach ($classSet as $file) {
            $fileParser->parse($file->getContents(), $file->getRelativePathname());
            $parsedErrors = $fileParser->getParsingErrors();

            foreach ($parsedErrors as $parsedError) {
                // TODO qua ce ne vogliamo fare qualcosa?
            }

            $ruleName = $input->getArgument('expression');
            $ruleFQCN = 'Arkitect\Expression\ForClasses\\'.$ruleName;
            $arguments = $input->getArgument('arguments');

            $rule = new $ruleFQCN(...$arguments);

            foreach ($fileParser->getClassDescriptions() as $classDescription) {
                $violations = new Violations();
                $rule->evaluate($classDescription, $violations, '');
                if (0 === $violations->count()) {
                    $output->writeln($classDescription->getFQCN());
                }
            }
        }

        return 0;
    }
}
