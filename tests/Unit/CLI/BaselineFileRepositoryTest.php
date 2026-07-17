<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\CLI;

use Arkitect\CLI\Baseline;
use Arkitect\CLI\BaselineFileRepository;
use Arkitect\Rules\Violation;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class BaselineFileRepositoryTest extends TestCase
{
    private string $baselineFilePath = __DIR__.'/_fixtures/checkhandler/repository-baseline.json';

    protected function tearDown(): void
    {
        if (file_exists($this->baselineFilePath)) {
            unlink($this->baselineFilePath);
        }
    }

    public function test_save_and_load_round_trip(): void
    {
        $violations = new Violations();
        $violations->add(new Violation('App\Controller\Shop', 'should have name end with Controller', 10, 'Controller/Shop.php'));

        $repository = new BaselineFileRepository();
        $repository->save(Baseline::fromViolations($violations), $this->baselineFilePath);

        $loaded = $repository->load($this->baselineFilePath);

        self::assertEquals($violations, $loaded->getViolations());
    }

    public function test_load_throws_when_the_file_does_not_exist(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Baseline file 'not-a-real-file.json' not found.");

        (new BaselineFileRepository())->load('not-a-real-file.json');
    }

    public function test_has_default_baseline_is_false_when_no_default_baseline_exists(): void
    {
        $this->inEmptyDirectory(static function (): void {
            self::assertFalse(BaselineFileRepository::hasDefaultBaseline());
        });
    }

    public function test_has_default_baseline_is_true_when_the_default_baseline_exists(): void
    {
        $this->inEmptyDirectory(static function (): void {
            file_put_contents(BaselineFileRepository::DEFAULT_FILENAME, '{"violations": []}');

            self::assertTrue(BaselineFileRepository::hasDefaultBaseline());

            unlink(BaselineFileRepository::DEFAULT_FILENAME);
        });
    }

    /**
     * The default baseline is looked up in the current working directory,
     * so run the callback from a fresh empty one to make tests deterministic.
     */
    private function inEmptyDirectory(callable $test): void
    {
        $cwd = (string) getcwd();
        $directory = sys_get_temp_dir().'/arkitect-baseline-test-'.uniqid();
        mkdir($directory);
        chdir($directory);

        try {
            $test();
        } finally {
            chdir($cwd);
            rmdir($directory);
        }
    }
}
