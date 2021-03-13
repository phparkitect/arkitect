<?php

declare(strict_types=1);

namespace Arkitect\Rules;

class Violation
{
    private string $fqcn;
    private string $error;
    private $line;

    public function __construct(string $fqcn, string $error, ?int $line = null)
    {
        $this->fqcn = $fqcn;
        $this->error = $error;
        $this->line = $line;
    }

    public static function create(string $fqcn, string $error): self
    {
        return new self($fqcn, $error);
    }

    public static function createWithErrorLine(string $fqcn, string $error, int $line): self
    {
        return new self($fqcn, $error, $line);
    }

    public function getFqcn(): string
    {
        return $this->fqcn;
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function getLine(): ?int
    {
        return $this->line;
    }
}
