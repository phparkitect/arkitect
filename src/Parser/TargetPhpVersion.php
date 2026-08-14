<?php

declare(strict_types=1);

namespace Arkitect\Parser;

final class TargetPhpVersion
{
    private const VALID = ['8.0', '8.1', '8.2', '8.3', '8.4', '8.5'];

    private string $version;

    private function __construct(string $version)
    {
        if (!\in_array($version, self::VALID, true)) {
            throw new \InvalidArgumentException("Invalid target PHP version '$version', expected one of: ".implode(', ', self::VALID));
        }

        $this->version = $version;
    }

    public static function create(?string $version): self
    {
        return new self($version ?? \PHP_MAJOR_VERSION.'.'.\PHP_MINOR_VERSION);
    }

    public function toString(): string
    {
        return $this->version;
    }
}
