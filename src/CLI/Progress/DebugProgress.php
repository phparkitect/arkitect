<?php
declare(strict_types=1);

namespace Arkitect\CLI\Progress;

use Arkitect\ClassSet;
use Symfony\Component\Console\Output\OutputInterface;

class DebugProgress implements Progress
{
    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function startFileSetAnalysis(ClassSet $set): void
    {
        $this->output->writeln("Start analyze dir {$set->getDir()}");
    }

    public function startParsingFile(string $file): void
    {
        $this->output->writeln("parsing $file");
    }

    public function endParsingFile(string $file): void
    {
    }

    public function endFileSetAnalysis(ClassSet $set): void
    {
    }
}
