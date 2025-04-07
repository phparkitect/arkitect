<?php

declare(strict_types=1);

namespace Arkitect\Tests\E2E\Cli;

use Arkitect\CLI\PhpArkitectApplication;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\ApplicationTester;

class InitCommandTest extends TestCase
{
    public function test_it_creates_a_file_in_default_dir(): void
    {
        $fs = vfsStream::setup()->url();

        $appTester = $this->runInit($fs);

        $output = $appTester->getDisplay();

        self::assertFileExists($fs.'/phparkitect.php');
        self::assertStringContainsString('Creating phparkitect.php file...', $output);
        self::assertStringContainsString('customize it and run with php bin/phparkitect check', $output);
    }

    public function test_it_creates_a_file_in_a_custom_dir(): void
    {
        $structure = [
            'nested' => [
                'path' => [],
            ],
        ];

        $fs = vfsStream::setup('root', null, $structure)->url();

        $appTester = $this->runInit($fs.'/nested/path');

        $output = $appTester->getDisplay();

        self::assertFileExists($fs.'/nested/path/phparkitect.php');
        self::assertStringContainsString('Creating phparkitect.php file...', $output);
        self::assertStringContainsString('customize it and run with php bin/phparkitect check', $output);
    }

    public function test_do_nothing_if_file_exists(): void
    {
        $structure = [
            'nested' => [
                'path' => [
                    'phparkitect.php' => '',
                ],
            ],
        ];

        $fs = vfsStream::setup('root', null, $structure)->url();

        $appTester = $this->runInit($fs.'/nested/path');

        self::assertStringContainsString(
            'File phparkitect.php found in current directory, nothing to do',
            $appTester->getDisplay()
        );
    }

    public function test_returns_error_if_directory_is_not_writable(): void
    {
        $fs = vfsStream::setup('root', 0000)->url();

        $appTester = $this->runInit($fs);

        self::assertStringContainsString(
            'Ops, it seems I cannot create the file in vfs://root',
            $appTester->getDisplay()
        );
    }

    private function runInit(string $path): ApplicationTester
    {
        $app = new PhpArkitectApplication();
        $app->setAutoExit(false);

        $appTester = new ApplicationTester($app);
        $appTester->run(['init', '--dest-dir' => $path]);

        return $appTester;
    }
}
