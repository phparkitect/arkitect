<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\CLI;

use Arkitect\CLI\BaselineFileRepository;
use Arkitect\CLI\Progress\VoidProgress;
use Arkitect\CLI\PruneBaselineHandler;
use Arkitect\CLI\PruneBaselineOptions;
use Arkitect\CLI\Runner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class PruneBaselineHandlerTest extends TestCase
{
    private string $baselineFilePath = __DIR__.'/_fixtures/checkhandler/pruned-baseline.json';

    protected function tearDown(): void
    {
        if (file_exists($this->baselineFilePath)) {
            unlink($this->baselineFilePath);
        }
    }

    public function test_prune_removes_fixed_entries_and_keeps_the_still_existing_ones(): void
    {
        $this->writeBaseline([
            // still occurring, but with a stale line number
            ['fqcn' => 'App\Foo', 'error' => 'should have a name that matches *Controller because we want uniform naming', 'line' => 99, 'filePath' => 'src/Foo.php'],
            // already fixed, must be pruned
            ['fqcn' => 'App\AlreadyFixed', 'error' => 'should have a name that matches *Controller because we want uniform naming', 'line' => 1, 'filePath' => 'src/AlreadyFixed.php'],
        ]);

        $handler = new PruneBaselineHandler(new Runner(), new BaselineFileRepository());
        $output = new BufferedOutput();

        $handler->pruneBaseline(
            $this->createOptions(configFilePath: __DIR__.'/_fixtures/checkhandler/configWithViolations.php'),
            new VoidProgress(),
            $output
        );

        self::assertStringContainsString("ℹ️ Baseline file '{$this->baselineFilePath}' pruned: 1 removed, 1 kept", $output->fetch());

        $baseline = json_decode((string) file_get_contents($this->baselineFilePath), true);

        self::assertCount(1, $baseline['violations']);
        self::assertSame('App\Foo', $baseline['violations'][0]['fqcn']);
        // the line number is refreshed with the current one
        self::assertNotSame(99, $baseline['violations'][0]['line']);
    }

    public function test_prune_never_adds_current_violations_to_an_empty_baseline(): void
    {
        $this->writeBaseline([]);

        $handler = new PruneBaselineHandler(new Runner(), new BaselineFileRepository());
        $output = new BufferedOutput();

        $handler->pruneBaseline(
            $this->createOptions(configFilePath: __DIR__.'/_fixtures/checkhandler/configWithViolations.php'),
            new VoidProgress(),
            $output
        );

        self::assertStringContainsString('pruned: 0 removed, 0 kept', $output->fetch());

        $baseline = json_decode((string) file_get_contents($this->baselineFilePath), true);

        self::assertSame([], $baseline['violations']);
    }

    public function test_prune_fails_when_the_baseline_file_does_not_exist(): void
    {
        $handler = new PruneBaselineHandler(new Runner(), new BaselineFileRepository());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Baseline file '{$this->baselineFilePath}' not found.");

        $handler->pruneBaseline(
            $this->createOptions(configFilePath: __DIR__.'/_fixtures/checkhandler/configWithViolations.php'),
            new VoidProgress(),
            new BufferedOutput()
        );
    }

    private function writeBaseline(array $violations): void
    {
        file_put_contents($this->baselineFilePath, json_encode(['violations' => $violations]));
    }

    private function createOptions(string $configFilePath): PruneBaselineOptions
    {
        return new PruneBaselineOptions(
            configFilePath: $configFilePath,
            targetPhpVersion: null,
            autoloadFilePath: null,
            ignoreBaselineLinenumbers: false,
            baselineFilePath: $this->baselineFilePath,
        );
    }
}
