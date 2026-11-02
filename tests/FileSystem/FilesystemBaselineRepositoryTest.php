<?php

declare(strict_types=1);

namespace Arkitect\Tests\FileSystem;

use Arkitect\Baseline;
use Arkitect\FileSystem\FilesystemBaselineRepository;
use PHPUnit\Framework\TestCase;

final class FilesystemBaselineRepositoryTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/arkitect-baseline-'.bin2hex(random_bytes(6));
        mkdir($this->root, recursive: true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->root.'/*') ?: [] as $file) {
            @chmod($file, 0o644);
            @unlink($file);
        }

        @chmod($this->root, 0o755);
        @rmdir($this->root);
    }

    public function test_a_baseline_survives_a_round_trip(): void
    {
        $repository = new FilesystemBaselineRepository($this->root);

        $repository->write('ba.json', Baseline::empty());

        self::assertSame(0, \count($repository->read('ba.json')));
    }

    public function test_reading_one_that_is_not_there_stops_the_run(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No baseline at');

        (new FilesystemBaselineRepository($this->root))->read('ba.json');
    }

    /**
     * The outcome worth refusing: a write that failed quietly would tell the
     * user the violations were accepted, and the next run would report every
     * one of them as new.
     */
    public function test_a_write_that_cannot_land_stops_the_run(): void
    {
        chmod($this->root, 0o500);

        if (is_writable($this->root)) {
            self::markTestSkipped('running as a user that ignores directory permissions');
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not write the baseline');

        (new FilesystemBaselineRepository($this->root))->write('ba.json', Baseline::empty());
    }

    public function test_a_baseline_that_cannot_be_read_stops_the_run(): void
    {
        file_put_contents($this->root.'/ba.json', '[]');
        chmod($this->root.'/ba.json', 0o000);

        if (is_readable($this->root.'/ba.json')) {
            self::markTestSkipped('running as a user that ignores file permissions');
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not read the baseline');

        (new FilesystemBaselineRepository($this->root))->read('ba.json');
    }
}
