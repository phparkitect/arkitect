<?php
declare(strict_types=1);

namespace Arkitect\CLI\Progress;

use Arkitect\ClassSet;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @psalm-suppress UndefinedDocblockClass
 * @psalm-suppress UndefinedClass
 */
class ProgressBarProgress implements Progress
{
    /** @var OutputInterface */
    private $output;

    /** @var ProgressBar */
    private $progress;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
        $this->progress = new ProgressBar($output);
    }

    public function startFileSetAnalysis(ClassSet $set): void
    {
        $this->output->writeln("analyze class set {$set->getDir()}");
        $this->output->writeln('');
        $this->progress = new ProgressBar($this->output, iterator_count($set));

        $this->progress->start();
    }

    public function startParsingFile(string $file): void
    {
    }

    public function endParsingFile(string $file): void
    {
        $this->progress->advance();
    }

    public function endFileSetAnalysis(ClassSet $set): void
    {
        $this->progress->finish();
        $this->output->writeln('');
        $this->output->writeln('');
    }
}
