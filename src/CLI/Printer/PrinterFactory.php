<?php
declare(strict_types=1);

namespace Arkitect\CLI\Printer;

final class PrinterFactory
{
    public function create(string $format): Printer
    {
        switch ($format) {
            case 'json':
                return new JsonPrinter();
            default:
                return new TextPrinter();
        }
    }
}
