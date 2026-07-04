<?php

declare(strict_types=1);

namespace Arkitect\Tests\E2E\Cli;

class GenerateBaselineCommandTest extends CommandTestCase
{
    public function test_generates_baseline_with_default_filename(): void
    {
        $cmdTester = $this->runGenerateBaseline(__DIR__.'/../_fixtures/configMvcForYieldBug.php');

        self::assertCommandWasSuccessful($cmdTester);
        self::assertStringContainsString("ℹ️ Baseline file '{$this->defaultBaselineFilename}' created!", $cmdTester->getErrorOutput());
        self::assertFileExists($this->defaultBaselineFilename);
    }

    public function test_generates_baseline_with_custom_filename(): void
    {
        $cmdTester = $this->runGenerateBaseline(__DIR__.'/../_fixtures/configMvcForYieldBug.php', $this->customBaselineFilename);

        self::assertCommandWasSuccessful($cmdTester);
        self::assertStringContainsString("ℹ️ Baseline file '{$this->customBaselineFilename}' created!", $cmdTester->getErrorOutput());
        self::assertFileExists($this->customBaselineFilename);
    }

    public function test_generated_baseline_contains_current_violations(): void
    {
        $this->runGenerateBaseline(__DIR__.'/../_fixtures/configMvcForYieldBug.php', $this->customBaselineFilename);

        $baseline = json_decode((string) file_get_contents($this->customBaselineFilename), true);

        self::assertCount(1, $baseline['violations']);
        self::assertSame('App\Controller\Foo', $baseline['violations'][0]['fqcn']);
    }

    public function test_line_numbers_can_be_left_out_of_the_baseline(): void
    {
        $this->runGenerateBaseline(__DIR__.'/../_fixtures/configMvc.php', $this->customBaselineFilename);

        $baseline = json_decode((string) file_get_contents($this->customBaselineFilename), true);
        $lineNumbers = array_column($baseline['violations'], 'line');

        self::assertNotEquals([], array_filter($lineNumbers));

        $this->runGenerateBaseline(__DIR__.'/../_fixtures/configMvc.php', $this->customBaselineFilename, true);

        $baseline = json_decode((string) file_get_contents($this->customBaselineFilename), true);
        $lineNumbers = array_column($baseline['violations'], 'line');

        self::assertEquals([], array_filter($lineNumbers));
    }

    public function test_returns_error_when_config_file_does_not_exist(): void
    {
        $cmdTester = $this->runGenerateBaseline(__DIR__.'/not-existing-config.php');

        self::assertCommandExitedWithError($cmdTester);
        self::assertFileDoesNotExist($this->defaultBaselineFilename);
    }
}
