<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

interface Parser
{
    public function parse(string $fileContent, string $filename, array $classDescriptionToParse): array;

    public function getClassDescriptions(): array;

    public function getParsingErrors(): array;

    public function getClassDescriptionsParsed(): ClassDescriptionCollection;
}
