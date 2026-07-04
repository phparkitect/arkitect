<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

final class Autoloader
{
    /**
     * @psalm-suppress UnresolvableInclude
     */
    public static function load(?string $filePath, OutputInterface $output): void
    {
        if (null === $filePath) {
            return;
        }

        Assert::file($filePath, "Cannot find '$filePath'");

        require_once $filePath;

        $output->writeln("Autoload file '$filePath' added");
    }
}
