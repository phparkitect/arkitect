<?php

declare(strict_types=1);

namespace Arkitect\Rules;

class Violation
{
    private string $fqcn;
    private string $error;

    public function __construct(string $fqcn, string $error)
    {
        $this->fqcn = $fqcn;
        $this->error = $error;
    }

    public static function create(string $fqcn, string $error): self
    {
        return new self($fqcn, $error);
    }

    public function getFqcn(): string
    {
        return $this->fqcn;
    }

    public function getError(): string
    {
        return $this->error;
    }
}
