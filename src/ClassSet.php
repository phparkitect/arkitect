<?php

namespace Arkitect;

use Arkitect\Analyzer\FileParser;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ClassSet
{
    /**
     * @var Finder
     */
    private $fileIterator;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var FileParser
     */
    private $parser;

    private function __construct()
    {
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

        $set = new self();
        $set->fileIterator = $finder;
        $set->dispatcher = new EventDispatcher();
        $set->parser = new FileParser($set->dispatcher);

        return $set;
    }

    public function run(): void
    {
        /** @var SplFileInfo $file */
        foreach($this->fileIterator as $file)
        {
            $this->parser->parse($file->getRelativePath(), $file->getContents());
        }
    }

    public function addSubScriber(EventSubscriberInterface $subscriber): void
    {
        $this->dispatcher->addSubscriber($subscriber);
    }
}
