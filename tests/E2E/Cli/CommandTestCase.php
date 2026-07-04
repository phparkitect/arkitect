<?php

declare(strict_types=1);

namespace Arkitect\Tests\E2E\Cli;

use Arkitect\CLI\PhpArkitectApplication;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\ApplicationTester;

abstract class CommandTestCase extends TestCase
{
    protected const SUCCESS_CODE = 0;

    protected const ERROR_CODE = 1;

    protected string $customBaselineFilename = __DIR__.'/my-baseline.json';

    protected string $defaultBaselineFilename = 'phparkitect-baseline.json';

    protected function tearDown(): void
    {
        if (file_exists($this->customBaselineFilename)) {
            unlink($this->customBaselineFilename);
        }
        if (file_exists($this->defaultBaselineFilename)) {
            unlink($this->defaultBaselineFilename);
        }
    }

    protected function runApplication(array $input): ApplicationTester
    {
        $app = new PhpArkitectApplication();
        $app->setAutoExit(false);

        $appTester = new ApplicationTester($app);
        $appTester->run($input, ['capture_stderr_separately' => true]);

        return $appTester;
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

        return $this->runApplication($input);
    }

    protected static function assertCommandExitedWithError(ApplicationTester $applicationTester): void
    {
        self::assertEquals(self::ERROR_CODE, $applicationTester->getStatusCode());
    }

    protected static function assertCommandWasSuccessful(ApplicationTester $applicationTester): void
    {
        self::assertEquals(self::SUCCESS_CODE, $applicationTester->getStatusCode());
    }
}
