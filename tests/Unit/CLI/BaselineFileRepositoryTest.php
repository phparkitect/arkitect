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

    public function test_exists_is_false_when_the_file_is_absent(): void
    {
        self::assertFalse((new BaselineFileRepository())->exists($this->baselineFilePath));
    }

    public function test_exists_is_true_when_the_file_is_present(): void
    {
        $repository = new BaselineFileRepository();
        $repository->save(Baseline::empty(), $this->baselineFilePath);

        self::assertTrue($repository->exists($this->baselineFilePath));
    }
}
