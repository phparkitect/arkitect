<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Exceptions\PhpVersionNotValidException;

class TargetPhpVersion
{
    public const VALID_PHP_VERSIONS = [
        '7.1',
        '7.2',
        '7.3',
        '7.4',
        '8.0',
        '8.1',
    ];

    /** @var string|null */
    private $version;

    private function __construct(?string $version)
    {
        $this->version = $version;
    }

    public static function create(?string $version): self
    {
        if (null === $version) {
            return new self(phpversion());
        }

        if (!\in_array($version, (new self(null))::VALID_PHP_VERSIONS)) {
            throw new PhpVersionNotValidException($version);
        }

        return new self($version);
    }

    public function get(): ?string
    {
        return $this->version;
    }
}
