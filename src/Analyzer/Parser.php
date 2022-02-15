<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

use Arkitect\Rules\NotParsedClasses;
use Arkitect\Rules\ParsingErrors;

interface Parser
{
    public function parse(string $fileContent, string $filename, array $classDescriptionToParse, ParsingErrors $parsingErrors): array;

    public function getClassDescriptions(): array;

    public function getParsingErrors(): ParsingErrors;

    public function getClassDescriptionsParsed(): ClassDescriptionCollection;

    public function getNotParsedClasses(): NotParsedClasses;
}
