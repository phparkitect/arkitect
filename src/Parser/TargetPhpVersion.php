<?php

declare(strict_types=1);

namespace Arkitect\Parser;

/**
 * The PHP version the *analysed* project targets, which is not the one
 * running arkitect: a project on 8.0 is analysed as 8.0 by a tool that
 * needs 8.5 to run.
 */
enum TargetPhpVersion: string
{
    case Php80 = '8.0';
    case Php81 = '8.1';
    case Php82 = '8.2';
    case Php83 = '8.3';
    case Php84 = '8.4';
    case Php85 = '8.5';

    /**
     * `from()` would do, but its ValueError names the enum rather than the
     * versions, and this value is typed by hand in a config file.
     */
    public static function create(string $version): self
    {
        return self::tryFrom($version) ?? throw new \InvalidArgumentException(\sprintf("Invalid target PHP version '%s', expected one of: %s", $version, implode(', ', array_column(self::cases(), 'value'))));
    }

    /**
     * What the interpreter running arkitect happens to be. A PHP newer than
     * any case here fails rather than guessing, and says what to do about
     * it — the user never typed this version, so "invalid" would be a lie.
     */
    public static function current(): self
    {
        $running = \PHP_MAJOR_VERSION.'.'.\PHP_MINOR_VERSION;

        return self::tryFrom($running) ?? throw new \InvalidArgumentException(\sprintf('arkitect does not know PHP %s yet. Set targetPhpVersion() in your config to the version your project targets.', $running));
    }
}
