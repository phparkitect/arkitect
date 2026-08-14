<?php

declare(strict_types=1);

namespace Arkitect;

use Arkitect\FileSystem\FileRepository;
use Arkitect\Parser\Parser;
use Arkitect\Parser\ParseResult;
use Arkitect\Parser\ParsingError;
use Arkitect\Parser\TargetPhpVersion;

final class ProjectParser
{
    public function __construct(
        private readonly FileRepository $files,
    ) {
    }

    public function parse(TargetPhpVersion $targetPhpVersion): ParseResult
    {
        $parser = new Parser();
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
