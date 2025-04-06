<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\CLI\Printer;

use Arkitect\CLI\Printer\GitlabPrinter;
use Arkitect\Rules\Violation;
use PHPUnit\Framework\TestCase;
use Some\AnotherExampleClass;
use Some\ExampleClass;

class GitlabPrinterTest extends TestCase
{
    /**
     * Test the `print` method returns a valid JSON string
     * with violations details when violations exist.
     */
    public function test_print_with_violations(): void
    {
        $violation1 = new Violation(ExampleClass::class, 'Some error message', 42, 'tests/Unit/CLI/Printer/GitlabPrinterTest.php');
        $violation2 = new Violation(AnotherExampleClass::class, 'Another error message', null, 'tests/Unit/CLI/Printer/GitlabPrinterTest.php');

        $violationsCollection = [
            'RuleA' => [$violation1],
            'RuleB' => [$violation2],
        ];

        $printer = new GitlabPrinter();

        $result = $printer->print($violationsCollection);

        self::assertSame(<<<JSON
        [{"description":"Some error message","check_name":"RuleA.some-error-message","fingerprint":"7ddcfd42f5f2af3d00864ef959a0327f508cb5227aedca96d919d681a5dcde4a","severity":"major","location":{"path":"tests\/Unit\/CLI\/Printer\/GitlabPrinterTest.php"},"lines":{"begin":42}},{"description":"Another error message","check_name":"RuleB.another-error-message","fingerprint":"800c2ceafbf4023e401200186ecabdfe59891c5d6670e86571e3c50339df07dc","severity":"major","location":{"path":"tests\/Unit\/CLI\/Printer\/GitlabPrinterTest.php"},"lines":{"begin":1}}]
        JSON, $result);
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

class AnotherExampleClass
{
}
