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

    private string $customBaselineFilename = __DIR__.'/my-generated-baseline.json';

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

    public function test_creates_the_baseline_with_the_default_filename(): void
    {
        $cmdTester = $this->runGenerateBaseline(__DIR__.'/../_fixtures/configMvcForYieldBug.php');

        self::assertEquals(self::SUCCESS_CODE, $cmdTester->getStatusCode());
        self::assertStringContainsString("ℹ️ Baseline file '{$this->defaultBaselineFilename}' created!", $cmdTester->getDisplay());
        self::assertFileExists($this->defaultBaselineFilename);

        $baseline = json_decode((string) file_get_contents($this->defaultBaselineFilename), true);

        self::assertCount(1, $baseline['violations']);
        self::assertSame('App\Controller\Foo', $baseline['violations'][0]['fqcn']);
    }

    public function test_creates_the_baseline_with_a_custom_filename(): void
    {
        $cmdTester = $this->runGenerateBaseline(
            __DIR__.'/../_fixtures/configMvcForYieldBug.php',
            $this->customBaselineFilename
        );

        self::assertEquals(self::SUCCESS_CODE, $cmdTester->getStatusCode());
        self::assertStringContainsString("ℹ️ Baseline file '{$this->customBaselineFilename}' created!", $cmdTester->getDisplay());
        self::assertFileExists($this->customBaselineFilename);
        self::assertFileDoesNotExist($this->defaultBaselineFilename);
    }

    public function test_deprecated_ignore_line_numbers_warns_and_still_writes_them(): void
    {
        $cmdTester = $this->runGenerateBaseline(
            __DIR__.'/../_fixtures/configIgnoreBaselineLineNumbers.php',
            $this->customBaselineFilename,
            true
        );

        self::assertEquals(self::SUCCESS_CODE, $cmdTester->getStatusCode());
        self::assertStringContainsString('is deprecated and has no effect', $cmdTester->getDisplay());

        $baseline = json_decode((string) file_get_contents($this->customBaselineFilename), true);

        self::assertNotNull($baseline['violations'][0]['line']);
    }

    public function test_fails_gracefully_when_the_config_file_does_not_exist(): void
    {
        $cmdTester = $this->runGenerateBaseline('not-a-real-config.php');

        self::assertEquals(self::ERROR_CODE, $cmdTester->getStatusCode());
        self::assertStringContainsString('not found', $cmdTester->getDisplay());
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
