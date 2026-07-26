<?php

declare(strict_types=1);

namespace Arkitect\Tests\E2E\Cli;

use Arkitect\CLI\PhpArkitectApplication;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\ApplicationTester;

class PruneBaselineCommandTest extends TestCase
{
    const SUCCESS_CODE = 0;

    const ERROR_CODE = 1;

    private string $customBaselineFilename = __DIR__.'/my-pruned-baseline.json';

    protected function tearDown(): void
    {
        if (file_exists($this->customBaselineFilename)) {
            unlink($this->customBaselineFilename);
        }
    }

    public function test_prunes_fixed_violations_and_keeps_the_still_existing_ones(): void
    {
        $configFilePath = __DIR__.'/../_fixtures/configMvcForYieldBug.php';

        // Produce a baseline matching the current violation
        $this->runCommand(['generate-baseline', '--config' => $configFilePath, 'filename' => $this->customBaselineFilename]);

        // Add a fake, already-fixed entry
        $baseline = json_decode((string) file_get_contents($this->customBaselineFilename), true);
        $baseline['violations'][] = [
            'fqcn' => 'App\Controller\AlreadyFixed',
            'error' => 'should have a name that matches *Controller because all controllers should be end name with Controller',
            'line' => 1,
            'filePath' => 'Controller/AlreadyFixed.php',
        ];
        file_put_contents($this->customBaselineFilename, json_encode($baseline));

        $cmdTester = $this->runCommand(['prune-baseline', '--config' => $configFilePath, 'filename' => $this->customBaselineFilename]);

        self::assertEquals(self::SUCCESS_CODE, $cmdTester->getStatusCode());
        self::assertStringContainsString("ℹ️ Baseline file '{$this->customBaselineFilename}' pruned: 1 removed, 1 kept", $cmdTester->getDisplay());

        $pruned = json_decode((string) file_get_contents($this->customBaselineFilename), true);

        self::assertCount(1, $pruned['violations']);
        self::assertSame('App\Controller\Foo', $pruned['violations'][0]['fqcn']);

        // The pruned baseline still makes the check pass
        $cmdTester = $this->runCommand(['check', '--config' => $configFilePath, '--use-baseline' => $this->customBaselineFilename]);

        self::assertEquals(self::SUCCESS_CODE, $cmdTester->getStatusCode());
        self::assertStringNotContainsString('💡', $cmdTester->getErrorOutput());
    }

    public function test_pruning_an_up_to_date_baseline_changes_nothing(): void
    {
        $configFilePath = __DIR__.'/../_fixtures/configMvcForYieldBug.php';

        $this->runCommand(['generate-baseline', '--config' => $configFilePath, 'filename' => $this->customBaselineFilename]);

        $cmdTester = $this->runCommand(['prune-baseline', '--config' => $configFilePath, 'filename' => $this->customBaselineFilename]);

        self::assertEquals(self::SUCCESS_CODE, $cmdTester->getStatusCode());
        self::assertStringContainsString('pruned: 0 removed, 1 kept', $cmdTester->getDisplay());
    }

    /**
     * Baselines written by older versions with --ignore-baseline-linenumbers
     * store no line number: pruning keeps that format instead of silently
     * upgrading the file.
     */
    public function test_pruning_preserves_a_baseline_stored_without_line_numbers(): void
    {
        $configFilePath = __DIR__.'/../_fixtures/configIgnoreBaselineLineNumbers.php';

        $this->runCommand([
            'generate-baseline',
            '--config' => $configFilePath,
            'filename' => $this->customBaselineFilename,
        ]);
        $this->stripLineNumbersFrom($this->customBaselineFilename);

        $cmdTester = $this->runCommand(['prune-baseline', '--config' => $configFilePath, 'filename' => $this->customBaselineFilename]);

        self::assertEquals(self::SUCCESS_CODE, $cmdTester->getStatusCode());
        self::assertStringContainsString('pruned: 0 removed, 3 kept', $cmdTester->getDisplay());

        $pruned = json_decode((string) file_get_contents($this->customBaselineFilename), true);

        self::assertCount(3, $pruned['violations']);
        foreach ($pruned['violations'] as $violation) {
            self::assertNull($violation['line'], 'pruning must not reintroduce the line numbers the baseline was generated without');
        }

        // the pruned baseline still makes the check pass, as the generated one did
        $cmdTester = $this->runCommand([
            'check',
            '--config' => $configFilePath,
            '--use-baseline' => $this->customBaselineFilename,
        ]);

        self::assertEquals(self::SUCCESS_CODE, $cmdTester->getStatusCode());
        self::assertStringNotContainsString('💡', $cmdTester->getErrorOutput());
    }

    public function test_fails_gracefully_when_the_baseline_file_does_not_exist(): void
    {
        $configFilePath = __DIR__.'/../_fixtures/configMvcForYieldBug.php';

        $cmdTester = $this->runCommand(['prune-baseline', '--config' => $configFilePath, 'filename' => $this->customBaselineFilename]);

        self::assertEquals(self::ERROR_CODE, $cmdTester->getStatusCode());
        self::assertStringContainsString('not found', $cmdTester->getDisplay());
    }

    protected function runCommand(array $input): ApplicationTester
    {
        $app = new PhpArkitectApplication();
        $app->setAutoExit(false);

        $appTester = new ApplicationTester($app);
        $appTester->run($input, ['capture_stderr_separately' => true]);

        return $appTester;
    }

    private function stripLineNumbersFrom(string $baselineFilename): void
    {
        $baseline = json_decode((string) file_get_contents($baselineFilename), true);

        foreach ($baseline['violations'] as $idx => $violation) {
            $baseline['violations'][$idx]['line'] = null;
        }

        file_put_contents($baselineFilename, json_encode($baseline));
    }
}
