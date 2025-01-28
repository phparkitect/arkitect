<?php

declare(strict_types=1);

namespace Arkitect\Rules;

class ParsingError
{
    private string $relativeFilePath;

    private string $error;

    public function __construct(string $relativeFilePath, string $error)
    {
        $this->error = $error;
        $this->relativeFilePath = $relativeFilePath;
    }

    public static function create(string $relativeFilePath, string $error): self
    {
        return new self($relativeFilePath, $error);
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function getRelativeFilePath(): string
    {
        return $this->relativeFilePath;
    }
}
