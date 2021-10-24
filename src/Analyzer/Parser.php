<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

interface Parser
{
    public function parse(string $fileContent, string $filename): void;

    public function getClassDescriptions(): array;

    public function getParsingErrors(): array;
}
