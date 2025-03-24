<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Composer\InstalledVersions;

class Version
{
    public static function get(): string
    {
        return InstalledVersions::getVersion('phparkitect/phparkitect') ?? 'UNKNOWN';
    }
}
