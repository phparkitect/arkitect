<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Printer;

use Arkitect\Printer\JsonPrinter;
use Arkitect\Printer\PrinterFactory;
use Arkitect\Printer\TextPrinter;
use PHPUnit\Framework\TestCase;

class PrinterFactoryTest extends TestCase
{
    /**
     * Test if the create method returns a JsonPrinter when the format is 'json'.
     */
    public function test_create_returns_json_printer_for_json_format(): void
    {
        $factory = new PrinterFactory();

        $printer = $factory->create('json');

        $this->assertInstanceOf(JsonPrinter::class, $printer);
    }

    /**
     * Test if the create method returns a TextPrinter when the format is not 'json'.
     */
    public function test_create_returns_text_printer_for_non_json_format(): void
    {
        $factory = new PrinterFactory();

        $printer = $factory->create('text');

        $this->assertInstanceOf(TextPrinter::class, $printer);
    }
}
