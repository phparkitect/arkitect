<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

use Symfony\Component\Finder\SplFileInfo;

class FQCNToFilePathResolver
{
    /**
     * PSR-4 prefix → list of base directories, sorted by prefix length descending
     * so the most specific prefix is tried first.
     *
     * @var array<string, list<string>>
     */
    private array $psr4Map;

    /**
     * @param array<string, list<string>> $psr4Map
     */
    private function __construct(array $psr4Map)
    {
        uksort($psr4Map, static fn (string $a, string $b): int => \strlen($b) - \strlen($a));
        $this->psr4Map = $psr4Map;
    }

    public static function create(): self
    {
        $composerJsonPath = self::findComposerJson((string) getcwd());

        if (null === $composerJsonPath) {
            return new self([]);
        }

        return self::fromComposerJson($composerJsonPath);
    }

    public static function fromComposerJson(string $composerJsonPath): self
    {
        $content = file_get_contents($composerJsonPath);

        if (false === $content) {
            return new self([]);
        }

        $data = json_decode($content, true);

        if (!\is_array($data)) {
            return new self([]);
        }

        $baseDir = \dirname($composerJsonPath);
        $psr4Map = [];

        foreach (['autoload', 'autoload-dev'] as $section) {
            if (!isset($data[$section]['psr-4']) || !\is_array($data[$section]['psr-4'])) {
                continue;
            }

            foreach ($data[$section]['psr-4'] as $prefix => $dirs) {
                if (\is_string($dirs)) {
                    $dirs = [$dirs];
                }

                if (!isset($psr4Map[$prefix])) {
                    $psr4Map[$prefix] = [];
                }

                foreach ($dirs as $dir) {
                    $psr4Map[$prefix][] = $baseDir.\DIRECTORY_SEPARATOR.rtrim($dir, '/\\');
                }
            }
        }

        return new self($psr4Map);
    }

    public function resolve(string $fqcn): ?SplFileInfo
    {
        $fqcn = ltrim($fqcn, '\\');

        foreach ($this->psr4Map as $prefix => $baseDirs) {
            if (0 !== strncmp($fqcn, $prefix, \strlen($prefix))) {
                continue;
            }

            $relativeClass = substr($fqcn, \strlen($prefix));
            $relativeFile = str_replace('\\', \DIRECTORY_SEPARATOR, $relativeClass).'.php';

            foreach ($baseDirs as $baseDir) {
                $realPath = realpath($baseDir.\DIRECTORY_SEPARATOR.$relativeFile);

                if (false !== $realPath) {
                    return new SplFileInfo($realPath, $relativeFile, $baseDir);
                }
            }
        }

        return null;
    }

    private static function findComposerJson(string $startDir): ?string
    {
        $dir = $startDir;

        while (true) {
            $candidate = $dir.\DIRECTORY_SEPARATOR.'composer.json';

            if (file_exists($candidate)) {
                return $candidate;
            }

            $parent = \dirname($dir);

            if ($parent === $dir) {
                return null;
            }

            $dir = $parent;
        }
    }
}
