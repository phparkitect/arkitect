<?php

declare(strict_types=1);

namespace Arkitect\Parser;

final class ProjectParser
{
    public function parse(string $rootPath, TargetPhpVersion $targetPhpVersion): ParseResult
    {
        $parser = new Parser();
        $classes = [];
        $errors = [];

        foreach ($this->phpFilesUnder($rootPath) as $relativePath => $absolutePath) {
            $content = @file_get_contents($absolutePath);

            if (false === $content) {
                $errors[] = new ParsingError($relativePath, "could not read '$absolutePath'");
                continue;
            }

            $result = $parser->parse($content, $relativePath, $targetPhpVersion);
            $classes = [...$classes, ...$result->classes];
            $errors = [...$errors, ...$result->errors];
        }

        return new ParseResult($classes, $errors);
    }

    /** @return \Generator<string, string> relative path => absolute path */
    private function phpFilesUnder(string $rootPath): \Generator
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($rootPath, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }

            $absolutePath = $file->getPathname();
            yield substr($absolutePath, \strlen($rootPath) + 1) => $absolutePath;
        }
    }
}
