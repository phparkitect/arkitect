<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Exceptions\PhpVersionNotValidException;

class TargetPhpVersion
{
    public const PHP_7_4 = '7.4';
    public const PHP_8_0 = '8.0';
    public const PHP_8_1 = '8.1';
    public const PHP_8_2 = '8.2';
    public const PHP_8_3 = '8.3';
    public const PHP_8_4 = '8.4';

    public const VALID_PHP_VERSIONS = [
        '7.4',
        '8.0',
        '8.1',
        '8.2',
        '8.3',
        '8.4',
    ];

    private string $version;

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

    public static function latest(): self
    {
        return new self(self::PHP_8_4);
    }

    public static function oldest(): self
    {
        return new self(self::PHP_7_4);
    }

    public static function create(?string $version): self
    {
        return new self($version ?? phpversion());
    }

    public function get(): string
    {
        return $this->version;
    }
}
