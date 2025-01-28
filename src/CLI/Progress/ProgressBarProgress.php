<?php
declare(strict_types=1);

namespace Arkitect\CLI\Progress;

use Arkitect\ClassSet;
use OndraM\CiDetector\CiDetector;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @psalm-suppress UndefinedDocblockClass
 * @psalm-suppress UndefinedClass
 */
class ProgressBarProgress implements Progress
{
    private \Symfony\Component\Console\Output\NullOutput|OutputInterface $output;

    private ProgressBar $progress;

    public function __construct(OutputInterface $output)
    {
        if ((new CiDetector())->isCiDetected()) {
            $this->output = new NullOutput();
        } else {
            $this->output = $output;
        }
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
