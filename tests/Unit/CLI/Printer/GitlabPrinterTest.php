<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\CLI\Printer;

use Arkitect\CLI\Printer\GitlabPrinter;
use Arkitect\Rules\Violation;
use PHPUnit\Framework\TestCase;

class GitlabPrinterTest extends TestCase
{
    /**
     * Test the `print` method returns a valid JSON string
     * with violations details when violations exist.
     */
    public function test_print_with_violations(): void
    {
        $violation1 = $this->createMock(Violation::class);
        $violation1->method('getFqcn')->willReturn('Some\\ExampleClass');
        $violation1->method('getError')->willReturn('Some error message');
        $violation1->method('getLine')->willReturn(42);

        $violation2 = $this->createMock(Violation::class);
        $violation2->method('getFqcn')->willReturn('Another\\ExampleClass');
        $violation2->method('getError')->willReturn('Another error message');
        $violation2->method('getLine')->willReturn(null);

        $violationsCollection = [
            'RuleA' => [$violation1],
            'RuleB' => [$violation2],
        ];

        $printer = new GitlabPrinter();

        $result = $printer->print($violationsCollection);
        $decodedResult = json_decode($result, true);

        $this->assertIsString($result, 'Result should be a string');
        $this->assertJson($result, 'Result should be a valid JSON string');
        $this->assertCount(2, $decodedResult, 'Result should contain two violations');

        $this->assertSame('Some error message', $decodedResult[0]['description']);
        $this->assertSame('RuleA.some-error-message', $decodedResult[0]['check_name']);
        $this->assertSame(hash('sha256', 'RuleA.some-error-message'), $decodedResult[0]['fingerprint']);
        $this->assertSame('major', $decodedResult[0]['severity']);
        $this->assertSame(__DIR__.'/GitlabPrinterTest.php', $decodedResult[0]['location']['path']);
        $this->assertSame(42, $decodedResult[0]['lines']['begin']);

        $this->assertSame('Another error message', $decodedResult[1]['description']);
        $this->assertSame('RuleB.another-error-message', $decodedResult[1]['check_name']);
        $this->assertSame(hash('sha256', 'RuleB.another-error-message'), $decodedResult[1]['fingerprint']);
        $this->assertSame('major', $decodedResult[1]['severity']);
        $this->assertSame(__DIR__.'/GitlabPrinterTest.php', $decodedResult[1]['location']['path']);
        $this->assertSame(1, $decodedResult[1]['lines']['begin']);
    }

    /**
     * Test the `print` method returns an empty JSON array
     * when no violations are provided.
     */
    public function test_print_with_no_violations(): void
    {
        $violationsCollection = [];

        $printer = new GitlabPrinter();

        $result = $printer->print($violationsCollection);

        $this->assertIsString($result, 'Result should be a string');
        $this->assertJson($result, 'Result should be a valid JSON string');

        $decodedResult = json_decode($result, true);
        $this->assertEmpty($decodedResult, 'Result should be an empty array');
    }
}

namespace Some;

class ExampleClass
{
}

namespace Another;

class ExampleClass
{
}
