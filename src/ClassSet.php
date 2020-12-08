<?php
declare(strict_types=1);

namespace Arkitect;

use Symfony\Component\Finder\Finder;

class ClassSet implements \IteratorAggregate
{
    private \Iterator $fileIterator;

    private function __construct(\Iterator $fileIterator)
    {
        $this->fileIterator = $fileIterator;
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

        return new self($finder->getIterator());
    }

    public function getIterator()
    {
        return $this->fileIterator;
    }
}
