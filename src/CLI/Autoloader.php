<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

final class Autoloader
{
    /** @var \Closure(): bool */
    private \Closure $isRunningAsPhar;

    /**
     * @param \Closure(): bool|null $isRunningAsPhar
     */
    public function __construct(?\Closure $isRunningAsPhar = null)
    {
        $this->isRunningAsPhar = $isRunningAsPhar ?? static fn (): bool => '' !== \Phar::running();
    }

    /**
     * @psalm-suppress UnresolvableInclude
     */
    public function load(?string $filePath, OutputInterface $output): void
    {
        if (null === $filePath) {
            // the phar bundles its own dependencies, so without an explicit
            // autoload file the user's classes cannot be resolved
            if (($this->isRunningAsPhar)()) {
                throw new \RuntimeException('The --autoload option is required when running phparkitect as a PHAR');
            }

            return;
        }

        Assert::file($filePath, "Cannot find '$filePath'");

        require_once $filePath;

        $output->writeln("Autoload file '$filePath' added");
    }
}
