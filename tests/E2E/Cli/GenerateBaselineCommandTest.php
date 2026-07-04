<?php

declare(strict_types=1);

namespace Arkitect\Tests\E2E\Cli;

use Arkitect\CLI\PhpArkitectApplication;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\ApplicationTester;

class GenerateBaselineCommandTest extends TestCase
{
    const SUCCESS_CODE = 0;

    const ERROR_CODE = 1;

    private string $customBaselineFilename = __DIR__.'/my-baseline.json';

    private string $defaultBaselineFilename = 'phparkitect-baseline.json';

    protected function tearDown(): void
    {
        if (file_exists($this->customBaselineFilename)) {
            unlink($this->customBaselineFilename);
        }
        if (file_exists($this->defaultBaselineFilename)) {
            unlink($this->defaultBaselineFilename);
        }
    }

    public function test_generates_baseline_with_default_filename(): void
    {
        $cmdTester = $this->runGenerateBaseline(__DIR__.'/../_fixtures/configMvcForYieldBug.php');

        self::assertEquals(self::SUCCESS_CODE, $cmdTester->getStatusCode());
        self::assertStringContainsString("ℹ️ Baseline file '{$this->defaultBaselineFilename}' created!", $cmdTester->getErrorOutput());
        self::assertFileExists($this->defaultBaselineFilename);
    }

    public function test_generates_baseline_with_custom_filename(): void
    {
        $cmdTester = $this->runGenerateBaseline(__DIR__.'/../_fixtures/configMvcForYieldBug.php', $this->customBaselineFilename);

        self::assertEquals(self::SUCCESS_CODE, $cmdTester->getStatusCode());
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

        self::assertEquals(self::ERROR_CODE, $cmdTester->getStatusCode());
        self::assertFileDoesNotExist($this->defaultBaselineFilename);
    }

    protected function runGenerateBaseline(
        string $configFilePath,
        ?string $filename = null,
        bool $ignoreBaselineNumbers = false,
    ): ApplicationTester {
        $input = ['generate-baseline', '--config' => $configFilePath];

        if (null !== $filename) {
            $input['filename'] = $filename;
        }

        if ($ignoreBaselineNumbers) {
            $input['--ignore-baseline-linenumbers'] = true;
        }

        $app = new PhpArkitectApplication();
        $app->setAutoExit(false);

        $appTester = new ApplicationTester($app);
        $appTester->run($input, ['capture_stderr_separately' => true]);

        return $appTester;
    }
}
