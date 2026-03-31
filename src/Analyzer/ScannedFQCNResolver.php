<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Resolves FQCNs to absolute file paths by scanning directories upfront and
 * building an in-memory index from namespace + class/interface/trait/enum
 * declarations extracted via regex.
 *
 * This approach works for any PHP code layout, not just PSR-4.
 */
class ScannedFQCNResolver
{
    /** @var array<string, string> FQCN → absolute file path */
    private array $index = [];

    /**
     * @param list<string> $directories absolute paths of directories to scan
     */
    public function __construct(array $directories)
    {
        if ([] === $directories) {
            return;
        }

        $finder = (new Finder())
            ->files()
            ->in($directories)
            ->name('*.php')
            ->ignoreUnreadableDirs(true)
            ->ignoreVCS(true);

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $realPath = $file->getRealPath();
            if (false === $realPath) {
                continue;
            }

            foreach ($this->extractFQCNs($file->getContents()) as $fqcn) {
                $this->index[$fqcn] = $realPath;
            }
        }
    }

    /**
     * Build a resolver by scanning the directories listed in a composer.json
     * autoload and autoload-dev psr-4/classmap sections.
     */
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
        $directories = [];

        foreach (['autoload', 'autoload-dev'] as $section) {
            if (!isset($data[$section]) || !\is_array($data[$section])) {
                continue;
            }

            foreach (['psr-4', 'psr-0'] as $standard) {
                if (!isset($data[$section][$standard]) || !\is_array($data[$section][$standard])) {
                    continue;
                }

                foreach ($data[$section][$standard] as $dirs) {
                    foreach ((array) $dirs as $dir) {
                        $abs = $baseDir.\DIRECTORY_SEPARATOR.rtrim((string) $dir, '/\\');
                        if (is_dir($abs)) {
                            $directories[] = $abs;
                        }
                    }
                }
            }

            if (isset($data[$section]['classmap']) && \is_array($data[$section]['classmap'])) {
                foreach ($data[$section]['classmap'] as $path) {
                    $abs = $baseDir.\DIRECTORY_SEPARATOR.rtrim((string) $path, '/\\');
                    if (is_dir($abs)) {
                        $directories[] = $abs;
                    }
                }
            }
        }

        return new self(array_values(array_unique($directories)));
    }

    /**
     * Walk up from $startDir until composer.json is found, then delegate to
     * fromComposerJson(). Returns an empty resolver when not found.
     */
    public static function create(): self
    {
        $composerJsonPath = self::findComposerJson((string) getcwd());

        if (null === $composerJsonPath) {
            return new self([]);
        }

        return self::fromComposerJson($composerJsonPath);
    }

    public function resolve(string $fqcn): ?string
    {
        return $this->index[ltrim($fqcn, '\\')] ?? null;
    }

    /**
     * @return list<string> all FQCNs declared in the given PHP source
     */
    private function extractFQCNs(string $source): array
    {
        $namespace = '';

        if (preg_match('/^\s*namespace\s+([\w\\\\]+)\s*[;{]/m', $source, $nsMatch)) {
            $namespace = $nsMatch[1];
        }

        if (0 === preg_match_all(
            '/^\s*(?:(?:abstract|final|readonly)\s+)*(?:class|interface|trait|enum)\s+(\w+)/m',
            $source,
            $classMatches
        )) {
            return [];
        }

        $fqcns = [];

        foreach ($classMatches[1] as $className) {
            $fqcns[] = '' !== $namespace ? $namespace.'\\'.$className : $className;
        }

        return $fqcns;
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
