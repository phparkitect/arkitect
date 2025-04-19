<?php
declare(strict_types=1);

namespace Arkitect\CLI\Printer;

final class PrinterFactory
{
    public static function default(): string
    {
        return Printer::FORMAT_TEXT;
    }

    public static function create(string $format): Printer
    {
        switch ($format) {
            case Printer::FORMAT_GITLAB:
                return new GitlabPrinter();
            case Printer::FORMAT_JSON:
                return new JsonPrinter();
            default:
                return new TextPrinter();
        }
    }
}
