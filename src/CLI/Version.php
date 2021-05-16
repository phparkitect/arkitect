<?php

declare(strict_types=1);

namespace Arkitect\CLI;

class Version
{
    private const COMPOSER_PATH = __DIR__.'/../../composer.json';

    public static function get(): string
    {
        $content = file_get_contents(self::COMPOSER_PATH);
        $composerData = json_decode($content, true);

        return $composerData['version'];
    }
}
