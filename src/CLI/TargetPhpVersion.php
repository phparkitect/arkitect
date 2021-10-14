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

    public static function create(?string $version): self
    {
        $targetPhpVersion = new self();

        if (null === $version) {
            $targetPhpVersion->version = phpversion();

            return $targetPhpVersion;
        }

        if (!\in_array($version, (new self())::VALID_PHP_VERSIONS)) {
            throw new PhpVersionNotValidException($version);
        }

        $targetPhpVersion->version = $version;

        return $targetPhpVersion;
    }

    public function get(): ?string
    {
        return $this->version;
    }
}
