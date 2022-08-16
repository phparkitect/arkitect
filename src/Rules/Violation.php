<?php

declare(strict_types=1);

namespace Arkitect\Rules;

class Violation
{
    /** @var string */
    private $fqcn;

    /** @var string */
    private $error;

    /** @var int|null */
    private $line;

    public function __construct(string $fqcn, string $error, ?int $line = null)
    {
        $this->fqcn = $fqcn;
        $this->error = $error;
        $this->line = $line;
    }

    public static function create(string $fqcn, ViolationMessage $error): self
    {
        return new self($fqcn, $error->toString());
    }

    public static function createWithErrorLine(string $fqcn, ViolationMessage $error, int $line): self
    {
        return new self($fqcn, $error->toString(), $line);
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
