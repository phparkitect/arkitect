<?php
declare(strict_types=1);

namespace Arkitect;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\ClassDescriptionArrayParser;
use Arkitect\Analyzer\Events\ClassAnalyzed;
use Arkitect\Analyzer\FileParser;
use Arkitect\Analyzer\FilePath;
use Arkitect\Analyzer\Parser;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ClassSet
{
    private \Iterator $fileIterator;
    private \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher;
    private \Arkitect\Analyzer\Parser $parser;
    private FilePath $currentlyAnalyzedFile;

    private function __construct(\Iterator $fileIterator, EventDispatcherInterface $dispatcher, Parser $parser, FilePath $filePath)
    {
        $this->fileIterator = $fileIterator;
        $this->dispatcher = $dispatcher;
        $this->parser = $parser;
        $this->currentlyAnalyzedFile = $filePath;
    }

    public static function fromDir(string $directory): self
    {
        $finder = (new Finder())
            ->files()
            ->in($directory)
            ->name('*.php')
            ->sortByName()
            ->followLinks()
            ->ignoreUnreadableDirs(true)
            ->ignoreVCS(true);

        $eventDispatcher = new EventDispatcher();
        $currentlyAnalyzedFile = new FilePath();
        $fileParser = new FileParser();

        $fileParser->onClassAnalyzed(static function (ClassDescription $classDescription) use ($eventDispatcher, $currentlyAnalyzedFile): void {
            $classDescription->setFullPath($currentlyAnalyzedFile->toString());

            $eventDispatcher->dispatch(new ClassAnalyzed($classDescription));
        });

        return new self($finder->getIterator(), $eventDispatcher, $fileParser, $currentlyAnalyzedFile);
    }

    public static function fromArray(array $classDescriptions)
    {
        $eventDispatcher = new EventDispatcher();

        return new self(new \ArrayIterator($classDescriptions), $eventDispatcher, new ClassDescriptionArrayParser($eventDispatcher), new FilePath());
    }

    public function run(): void
    {
        /** @var SplFileInfo $file */
        foreach ($this->fileIterator as $file) {
            $this->currentlyAnalyzedFile->set($file->getRelativePath());

            $this->parser->parse($file->getContents());
        }
    }

    public function addSubscriber(EventSubscriberInterface $subscriber): void
    {
        $this->dispatcher->addSubscriber($subscriber);
    }
}
