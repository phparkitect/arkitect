<?php

declare(strict_types=1);

namespace Arkitect\CLI;

class Version
{
    private const COMPOSER_PATHS = [
        'composer.json',
        '../composer.json',
        '../../composer.json',
        '../../../composer.json',
        './vendor/phparkitect/phparkitect/composer.json',
    ];

    public static function get(): string
    {
        $pharPath = \Phar::running();

        if ($pharPath) {
            $content = file_get_contents("$pharPath/composer.json");
            $composerData = json_decode($content, true);

            return $composerData['version'] ?? 'UNKNOWN';
        }

        foreach (self::COMPOSER_PATHS as $composerPath) {
            if (!file_exists($composerPath)) {
                continue;
            }

            $content = file_get_contents($composerPath);
            $composerData = json_decode($content, true);

            return $composerData['version'] ?? 'UNKNOWN';
        }

        return 'UNKNOWN';
    }
}
