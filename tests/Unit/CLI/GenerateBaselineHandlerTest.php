<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\CLI;

use Arkitect\CLI\GenerateBaselineHandler;
use Arkitect\CLI\GenerateBaselineOptions;
use Arkitect\CLI\Progress\VoidProgress;
use Arkitect\CLI\Runner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class GenerateBaselineHandlerTest extends TestCase
{
    private string $generatedBaselineFilePath = __DIR__.'/_fixtures/checkhandler/generated-baseline.json';

    protected function tearDown(): void
    {
        if (file_exists($this->generatedBaselineFilePath)) {
            unlink($this->generatedBaselineFilePath);
        }
    }

    public function test_generate_baseline_writes_the_violations_to_a_file(): void
    {
        $handler = new GenerateBaselineHandler(new Runner());
        $output = new BufferedOutput();

        $handler->generateBaseline(
            $this->createOptions(configFilePath: __DIR__.'/_fixtures/checkhandler/configWithViolations.php'),
            new VoidProgress(),
            $output
        );

        self::assertFileExists($this->generatedBaselineFilePath);
        self::assertStringContainsString("ℹ️ Baseline file '{$this->generatedBaselineFilePath}' created!", $output->fetch());

        $baseline = json_decode((string) file_get_contents($this->generatedBaselineFilePath), true);

        self::assertCount(1, $baseline['violations']);
        self::assertSame('App\Foo', $baseline['violations'][0]['fqcn']);
    }

    public function test_generate_baseline_can_omit_line_numbers(): void
    {
        $handler = new GenerateBaselineHandler(new Runner());
        $output = new BufferedOutput();

        $handler->generateBaseline(
            $this->createOptions(
                configFilePath: __DIR__.'/_fixtures/checkhandler/configWithViolations.php',
                ignoreBaselineLinenumbers: true
            ),
            new VoidProgress(),
            $output
        );

        $baseline = json_decode((string) file_get_contents($this->generatedBaselineFilePath), true);

        self::assertCount(1, $baseline['violations']);
        self::assertNull($baseline['violations'][0]['line']);
    }

    public function test_generate_baseline_writes_an_empty_baseline_when_there_are_no_violations(): void
    {
        $handler = new GenerateBaselineHandler(new Runner());
        $output = new BufferedOutput();

        $handler->generateBaseline(
            $this->createOptions(configFilePath: __DIR__.'/_fixtures/checkhandler/configWithoutViolations.php'),
            new VoidProgress(),
            $output
        );

        self::assertFileExists($this->generatedBaselineFilePath);

        $baseline = json_decode((string) file_get_contents($this->generatedBaselineFilePath), true);

        self::assertSame([], $baseline['violations']);
    }

    private function createOptions(
        string $configFilePath,
        bool $ignoreBaselineLinenumbers = false,
    ): GenerateBaselineOptions {
        return new GenerateBaselineOptions(
            configFilePath: $configFilePath,
            targetPhpVersion: null,
            autoloadFilePath: null,
            ignoreBaselineLinenumbers: $ignoreBaselineLinenumbers,
            baselineFilePath: $this->generatedBaselineFilePath,
        );
    }
}
