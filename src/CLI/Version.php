<?php

declare(strict_types=1);

namespace Arkitect\CLI;

class Version
{
    public static function get(): string
    {
        $pharPath = \Phar::running();

        if ($pharPath) {
            $content = file_get_contents("$pharPath/composer.json");
        } else {
            $phparkitectRootPath = __DIR__.'/../../';
            $content = file_get_contents($phparkitectRootPath.'composer.json');
        }

        $composerData = json_decode($content, true);

        return $composerData['version'] ?? 'UNKNOWN';
    }
}
