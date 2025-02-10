<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Exceptions\PhpVersionNotValidException;

class TargetPhpVersion
{
    public const VALID_PHP_VERSIONS = [
        '7.4',
        '8.0',
        '8.1',
        '8.2',
        '8.3',
    ];

    /** @var string|null */
    private $version;

    private function __construct(string $version)
    {
        $versionNumbers = explode('.', $version);
        if (3 <= \count($versionNumbers)) {
            $version = $versionNumbers[0].'.'.$versionNumbers[1];
        }

        if (!\in_array($version, self::VALID_PHP_VERSIONS)) {
            throw new PhpVersionNotValidException($version);
        }

        $this->version = $version;
    }

    public static function create(?string $version): self
    {
        return new self($version ?? phpversion());
    }

    public function get(): ?string
    {
        return $this->version;
    }
}
