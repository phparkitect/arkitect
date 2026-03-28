<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

class ParsingError
{
    private string $relativeFilePath;

    private string $error;

    public function __construct(string $relativeFilePath, string $error)
    {
        $this->error = $error;
        $this->relativeFilePath = $relativeFilePath;
    }

    public function __toString(): string
    {
        return $this->error.' in file: '.$this->relativeFilePath;
    }

    public static function create(string $relativeFilePath, string $error): self
    {
        return new self($relativeFilePath, $error);
    }
}
