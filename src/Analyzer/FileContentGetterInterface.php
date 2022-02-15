<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

use Arkitect\Rules\NotParsedClasses;
use Arkitect\Rules\ParsingError;

interface FileContentGetterInterface
{
    public function open(string $classFQCN): void;

    public function getContent(): ?string;

    public function isContentAvailable(): bool;

    public function getError(): ?ParsingError;

    public function getFileName(): ?string;

    public function getNotParsedClasses(): NotParsedClasses;
}
