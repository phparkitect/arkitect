<?php
declare(strict_types=1);

namespace Arkitect;

use Arkitect\Analyzer\ClassDescriptionArrayParser;
use Arkitect\Analyzer\FileParser;
use Arkitect\Analyzer\Parser;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Finder\Finder;

class ClassSet
{
    private $fileIterator;
    private $dispatcher;
    private $parser;
    private $excludedFiles;

    private function __construct(\Iterator $fileIterator, EventDispatcherInterface $dispatcher, Parser $parser)
    {
        $this->fileIterator = $fileIterator;
        $this->dispatcher = $dispatcher;
        $this->parser = $parser;
        $this->excludedFiles = [];
    }

    public static function fromDir(string $directory): self
    {
        $finder = (new Finder())
            ->files()
            ->in($directory)
            ->name('*.php')
            ->followLinks()
            ->ignoreUnreadableDirs(true)
            ->ignoreVCS(true);

        $eventDispatcher = new EventDispatcher();

        return new self($finder->getIterator(), $eventDispatcher, new FileParser($eventDispatcher));
    }

    public static function fromArray(array $classDescriptions)
    {
        $eventDispatcher = new EventDispatcher();

        return new self(new \ArrayIterator($classDescriptions), $eventDispatcher, new ClassDescriptionArrayParser($eventDispatcher));
    }

    public function run(): void
    {
        foreach ($this->fileIterator as $file) {
            $this->parser->parse($file, $this->excludedFiles);
        }
    }

    public function addSubScriber(EventSubscriberInterface $subscriber): void
    {
        $this->dispatcher->addSubscriber($subscriber);
    }

    public function excludeFiles(array $excludedFiles): void
    {
        $this->excludedFiles = $excludedFiles;
    }
}
