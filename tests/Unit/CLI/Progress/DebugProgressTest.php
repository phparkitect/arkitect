<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\CLI\Progress;

use Arkitect\ClassSet;
use Arkitect\CLI\Progress\DebugProgress;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Output\OutputInterface;

class DebugProgressTest extends TestCase
{
    use ProphecyTrait;

    public function test_it_should_generate_text_on_start_parsing_file(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $debugProgress = new DebugProgress($output->reveal());

        $output->writeln('parsing filename')->shouldBeCalled();
        $debugProgress->startParsingFile('filename');
    }

    public function test_it_should_generate_text_on_start_file_set_analysis(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $debugProgress = new DebugProgress($output->reveal());

        $output->writeln('Start analyze dirs directory1, directory2')->shouldBeCalled();
        $debugProgress->startFileSetAnalysis(ClassSet::fromDir('directory1', 'directory2'));
    }

    public function test_it_should_not_generate_text_on_end_parsing_file(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $debugProgress = new DebugProgress($output->reveal());

        $output->writeln()->shouldNotBeCalled();
        $debugProgress->endParsingFile('filename');
    }

    public function test_it_should_not_generate_text_on_end_file_set_analysis(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $debugProgress = new DebugProgress($output->reveal());

        $output->writeln()->shouldNotBeCalled();
        $debugProgress->endFileSetAnalysis(ClassSet::fromDir('directory'));
    }
}
