<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

class GenericError
{
    private string $relativeFilePath;

    private $error;

    public function __construct(string $relativeFilePath, string $error)
    {
        $this->relativeFilePath = $relativeFilePath;
        $this->error = $error;
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
