<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Webmozart\Assert\Assert;

class ConfigBuilder
{
    public static function loadFromFile(string $filePath): Config
    {
        Assert::file($filePath, "Config file '$filePath' not found");

        $config = new Config();

        \Closure::fromCallable(function () use ($config, $filePath): ?bool {
            /** @psalm-suppress UnresolvableInclude $config */
            $configFunction = require $filePath;

            Assert::isCallable($configFunction);

            return $configFunction($config);
        })();

        return $config;
    }
}
