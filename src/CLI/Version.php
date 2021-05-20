<?php

declare(strict_types=1);

namespace Arkitect\CLI;

class Version
{
    private const COMPOSER_PATHS = [
        'phar://phparkitect.phar/composer.json',
        'composer.json',
        '../composer.json',
        '../../composer.json',
        '../../composer.json',
    ];

    public static function get(): string
    {
        foreach (self::COMPOSER_PATHS as $composerPath) {
            if (file_exists($composerPath)) {
                $content = file_get_contents($composerPath);
                $composerData = json_decode($content, true);

                return $composerData['version'];
            }
        }

        throw new \Exception('composer.json not found');
    }
}
