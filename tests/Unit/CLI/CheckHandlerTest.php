<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\CLI;

use Arkitect\CLI\CheckHandler;
use Arkitect\CLI\CheckOptions;
use Arkitect\CLI\Progress\VoidProgress;
use Arkitect\CLI\Runner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class CheckHandlerTest extends TestCase
{
    private string $generatedBaselineFilePath = __DIR__.'/_fixtures/checkhandler/generated-baseline.json';

    protected function tearDown(): void
    {
        if (file_exists($this->generatedBaselineFilePath)) {
            unlink($this->generatedBaselineFilePath);
        }
    }

    public function test_check_reports_violations(): void
    {
        $handler = new CheckHandler(new Runner());
        $output = new BufferedOutput();
        $violationsOutput = new BufferedOutput();

        $result = $handler->check(
            $this->createOptions(configFilePath: __DIR__.'/_fixtures/checkhandler/configWithViolations.php'),
            new VoidProgress(),
            $output,
            $violationsOutput
        );

        self::assertTrue($result->hasViolations());
        self::assertStringContainsString('App\Foo has 1 violations', $violationsOutput->fetch());
        self::assertStringContainsString('⚠️ 1 violations detected!', $output->fetch());
    }

    public function test_check_reports_success_when_there_are_no_violations(): void
    {
        $handler = new CheckHandler(new Runner());
        $output = new BufferedOutput();
        $violationsOutput = new BufferedOutput();

        $result = $handler->check(
            $this->createOptions(configFilePath: __DIR__.'/_fixtures/checkhandler/configWithoutViolations.php'),
            new VoidProgress(),
            $output,
            $violationsOutput
        );

        self::assertFalse($result->hasErrors());
        self::assertStringContainsString('✅ No violations detected', $output->fetch());
    }

    public function test_check_applies_the_baseline(): void
    {
        $handler = new CheckHandler(new Runner());
        $output = new BufferedOutput();

        $handler->generateBaseline(
            $this->createOptions(
                configFilePath: __DIR__.'/_fixtures/checkhandler/configWithViolations.php',
                generateBaseline: true,
                generateBaselineFilePath: $this->generatedBaselineFilePath
            ),
            new VoidProgress(),
            $output
        );

        $result = $handler->check(
            $this->createOptions(
                configFilePath: __DIR__.'/_fixtures/checkhandler/configWithViolations.php',
                baselineFilePath: $this->generatedBaselineFilePath
            ),
            new VoidProgress(),
            $output,
            new BufferedOutput()
        );

        self::assertFalse($result->hasErrors());
        self::assertStringContainsString("Baseline file '{$this->generatedBaselineFilePath}' found", $output->fetch());
    }

    public function test_generate_baseline_writes_the_violations_to_a_file(): void
    {
        $handler = new CheckHandler(new Runner());
        $output = new BufferedOutput();

        $handler->generateBaseline(
            $this->createOptions(
                configFilePath: __DIR__.'/_fixtures/checkhandler/configWithViolations.php',
                generateBaseline: true,
                generateBaselineFilePath: $this->generatedBaselineFilePath
            ),
            new VoidProgress(),
            $output
        );

        self::assertFileExists($this->generatedBaselineFilePath);
        self::assertStringContainsString("ℹ️ Baseline file '{$this->generatedBaselineFilePath}' created!", $output->fetch());

        $baseline = json_decode((string) file_get_contents($this->generatedBaselineFilePath), true);

        self::assertCount(1, $baseline['violations']);
        self::assertSame('App\Foo', $baseline['violations'][0]['fqcn']);
    }

    private function createOptions(
        string $configFilePath,
        ?string $baselineFilePath = null,
        bool $generateBaseline = false,
        ?string $generateBaselineFilePath = null,
    ): CheckOptions {
        return new CheckOptions(
            configFilePath: $configFilePath,
            targetPhpVersion: null,
            stopOnFailure: false,
            baselineFilePath: $baselineFilePath,
            skipBaseline: false,
            ignoreBaselineLinenumbers: false,
            generateBaseline: $generateBaseline,
            generateBaselineFilePath: $generateBaselineFilePath,
            format: 'text',
            autoloadFilePath: null,
        );
    }
}
