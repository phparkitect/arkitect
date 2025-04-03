<?php

declare(strict_types=1);

namespace Arkitect\Rules;

class Violation implements \JsonSerializable
{
    private string $fqcn;

    private ?int $line;

    private ?string $filePath;

    private string $error;

    public function __construct(string $fqcn, string $error, ?int $line = null, ?string $filePath = null)
    {
        $this->fqcn = $fqcn;
        $this->error = $error;
        $this->line = $line;
        $this->filePath = $filePath;
    }

    public static function create(string $fqcn, ViolationMessage $error, string $filePath): self
    {
        return new self($fqcn, $error->toString(), null, $filePath);
    }

    public static function createWithErrorLine(string $fqcn, ViolationMessage $error, int $line, string $filePath): self
    {
        return new self($fqcn, $error->toString(), $line, $filePath);
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

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public static function fromJson(array $json): self
    {
        return new self($json['fqcn'], $json['error'], $json['line'], $json['filePath'] ?? null);
    }
}
