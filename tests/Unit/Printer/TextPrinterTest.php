<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Printer;

use Arkitect\Printer\TextPrinter;
use Arkitect\Rules\Violation;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Arkitect\Printer\TextPrinter
 */
class TextPrinterTest extends TestCase
{
    public function test_prints_violation_without_line(): void
    {
        $violations = [
            'ExampleClass' => [
                new Violation('ExampleClass', 'Error message'),
            ],
        ];

        $printer = new TextPrinter();
        $result = $printer->print($violations);

        $expectedOutput = "\nExampleClass has 1 violations\n  Error message\n";

        $this->assertSame($expectedOutput, $result);
    }

    public function test_prints_violation_with_line(): void
    {
        $violations = [
            'ExampleClass' => [
                new Violation('ExampleClass', 'Error message', 42),
            ],
        ];

        $printer = new TextPrinter();
        $result = $printer->print($violations);

        $expectedOutput = "\nExampleClass has 1 violations\n  Error message (on line 42)\n";

        $this->assertSame($expectedOutput, $result);
    }

    public function test_prints_multiple_violations_grouped_by_fqcn(): void
    {
        $violations = [
            'ExampleClass' => [
                new Violation('ExampleClass', 'First error'),
                new Violation('ExampleClass', 'Second error', 10),
            ],
            'AnotherClass' => [
                new Violation('AnotherClass', 'Another error', 15),
            ],
        ];

        $printer = new TextPrinter();
        $result = $printer->print($violations);

        $expectedOutput = "\nExampleClass has 2 violations\n  First error\n  Second error (on line 10)\n\nAnotherClass has 1 violations\n  Another error (on line 15)\n";

        $this->assertSame($expectedOutput, $result);
    }
}
