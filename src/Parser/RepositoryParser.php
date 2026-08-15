<?php

declare(strict_types=1);

namespace Arkitect\Parser;

use Arkitect\FileSystem\FileRepository;
use Arkitect\ProjectParser;

final class RepositoryParser implements ProjectParser
{
    public function __construct(
        private readonly FileRepository $files,
    ) {
    }

    public function parse(TargetPhpVersion $targetPhpVersion): ParseResult
    {
        $parser = new ClassParser();
        $classes = [];
        $errors = [];

        foreach ($this->files->files() as $relativePath) {
            try {
                $content = $this->files->read($relativePath);
            } catch (\RuntimeException $e) {
                $errors[] = new ParsingError($relativePath, $e->getMessage());
                continue;
            }

            $result = $parser->parse($content, $relativePath, $targetPhpVersion);
            $classes = [...$classes, ...$result->classes];
            $errors = [...$errors, ...$result->errors];
        }

        return new ParseResult($classes, $errors);
    }
}
