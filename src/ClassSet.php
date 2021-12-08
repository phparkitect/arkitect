<?php
declare(strict_types=1);

namespace Arkitect;

use Symfony\Component\Finder\Finder;

class ClassSet implements \IteratorAggregate
{
    /** @var string */
    private $directory;

    /** @var array */
    private $exclude;

    private function __construct(string $directory)
    {
        $this->directory = $directory;
        $this->exclude = [];
    }

    public function excludePath(string $pattern): self
    {
        $this->exclude[] = Glob::toRegex($pattern);

        return $this;
    }

    public static function fromDir(string $directory): self
    {
        return new self($directory);
    }

    public function getDir(): string
    {
        return $this->directory;
    }

    public function getIterator(): \Traversable
    {
        $finder = (new Finder())
            ->files()
            ->in($this->directory)
            ->name('*.php')
            ->sortByName()
            ->followLinks()
            ->ignoreUnreadableDirs(true)
            ->ignoreVCS(true);

        if ($this->exclude) {
            $finder->notPath($this->exclude);
        }

        return $finder;
    }
}
