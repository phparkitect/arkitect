<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\CLI;

use Arkitect\CLI\BaselineFileRepository;
use Arkitect\CLI\CheckHandler;
use Arkitect\CLI\CheckOptions;
use Arkitect\CLI\GenerateBaselineHandler;
use Arkitect\CLI\GenerateBaselineOptions;
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
        $handler = new CheckHandler(new Runner(), new BaselineFileRepository());
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
        $handler = new CheckHandler(new Runner(), new BaselineFileRepository());
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
        $generateBaselineHandler = new GenerateBaselineHandler(new Runner(), new BaselineFileRepository());
        $output = new BufferedOutput();

        $generateBaselineHandler->generateBaseline(
            new GenerateBaselineOptions(
                configFilePath: __DIR__.'/_fixtures/checkhandler/configWithViolations.php',
                targetPhpVersion: null,
                autoloadFilePath: null,
                ignoreBaselineLinenumbers: false,
                baselineFilePath: $this->generatedBaselineFilePath,
            ),
            new VoidProgress(),
            $output
        );

        $handler = new CheckHandler(new Runner(), new BaselineFileRepository());

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

    private function createOptions(
        string $configFilePath,
        ?string $baselineFilePath = null,
    ): CheckOptions {
        return new CheckOptions(
            configFilePath: $configFilePath,
            targetPhpVersion: null,
            stopOnFailure: false,
            baselineFilePath: $baselineFilePath,
            skipBaseline: false,
            ignoreBaselineLinenumbers: false,
            format: 'text',
            autoloadFilePath: null,
        );
    }
}
