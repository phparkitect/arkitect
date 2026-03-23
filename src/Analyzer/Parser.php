<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

use Arkitect\Rules\GenericError;
use Arkitect\Rules\ParsingErrors;

interface Parser
{
    public function parse(string $fileContent, string $filename): ClassDescriptions|ParsingErrors|GenericError;
}
