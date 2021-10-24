<?php

declare(strict_types=1);

namespace Arkitect\Rules;

class ParsingError
{
    /** @var string */
    private $relativeFilePath;

    /** @var string */
    private $error;

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
