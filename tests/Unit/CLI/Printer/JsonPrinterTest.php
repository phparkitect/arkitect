<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\CLI\Printer;

use Arkitect\CLI\Printer\JsonPrinter;
use Arkitect\Rules\Violation;
use PHPUnit\Framework\TestCase;

class JsonPrinterTest extends TestCase
{
    /**
     * Test that print method returns correct JSON for an empty violations collection.
     */
    public function test_print_returns_correct_json_for_empty_violations(): void
    {
        $printer = new JsonPrinter();
        $result = $printer->print([]);

        $expected = json_encode([
            'totalViolations' => 0,
            'details' => [],
        ]);

        self::assertSame($expected, $result);
    }

    /**
     * Test that print method returns correct JSON for a single violation.
     */
    public function test_print_returns_correct_json_for_single_violation(): void
    {
        $violation = $this->createMock(Violation::class);
        $violation->method('getError')->willReturn('Error message');
        $violation->method('getLine')->willReturn(42);

        $violationsCollection = [
            'Some\\Class' => [$violation],
        ];

        $printer = new JsonPrinter();
        $result = $printer->print($violationsCollection);

        $expected = json_encode([
            'totalViolations' => 1,
            'details' => [
                'Some\\Class' => [
                    [
                        'error' => 'Error message',
                        'line' => 42,
                    ],
                ],
            ],
        ]);

        self::assertSame($expected, $result);
    }

    /**
     * Test that print method returns correct JSON for multiple violations.
     */
    public function test_print_returns_correct_json_for_multiple_violations(): void
    {
        $violation1 = $this->createMock(Violation::class);
        $violation1->method('getError')->willReturn('First error');
        $violation1->method('getLine')->willReturn(10);

        $violation2 = $this->createMock(Violation::class);
        $violation2->method('getError')->willReturn('Second error');

        $violationsCollection = [
            'ClassA' => [$violation1],
            'ClassB' => [$violation2],
        ];

        $printer = new JsonPrinter();
        $result = $printer->print($violationsCollection);

        $expected = json_encode([
            'totalViolations' => 2,
            'details' => [
                'ClassA' => [
                    [
                        'error' => 'First error',
                        'line' => 10,
                    ],
                ],
                'ClassB' => [
                    [
                        'error' => 'Second error',
                    ],
                ],
            ],
        ]);

        self::assertSame($expected, $result);
    }
}
