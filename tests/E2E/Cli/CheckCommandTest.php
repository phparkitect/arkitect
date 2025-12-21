<?php

declare(strict_types=1);

namespace Arkitect\Tests\E2E\Cli;

use Arkitect\CLI\PhpArkitectApplication;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\ApplicationTester;

class CheckCommandTest extends TestCase
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

    public function test_app_returns_error_with_multiple_violations(): void
    {
        $cmdTester = $this->runCheck(__DIR__.'/../_fixtures/configMvc.php');

        $expectedErrors = <<<'ERRORS'
        App\Controller\Foo has 2 violations
          should have a name that matches *Controller because we want uniform naming
          should implement ContainerAwareInterface because all controllers should be container aware

        App\Controller\ProductsController has 1 violations
          should implement ContainerAwareInterface because all controllers should be container aware

        App\Controller\UserController has 1 violations
          should implement ContainerAwareInterface because all controllers should be container aware

        App\Controller\YieldController has 1 violations
          should implement ContainerAwareInterface because all controllers should be container aware

        App\Domain\Model has 2 violations
          depends on App\Services\UserService, but should not depend on classes outside namespace App\Domain because we want protect our domain (on line 14)
          depends on App\Services\CartService, but should not depend on classes outside namespace App\Domain because we want protect our domain (on line 15)
        ERRORS;

        self::assertCommandExitedWithError($cmdTester);
        self::assertStringContainsString($expectedErrors, $cmdTester->getDisplay());
    }

    public function test_app_returns_single_error_because_there_is_stop_on_failure_param(): void
    {
        $cmdTester = $this->runCheck(__DIR__.'/../_fixtures/configMvc.php', true);

        $expectedErrors = <<<'ERRORS'
        App\Controller\Foo has 1 violations
          should implement ContainerAwareInterface because all controllers should be container aware
        ERRORS;

        self::assertCommandExitedWithError($cmdTester);
        self::assertStringContainsString($expectedErrors, $cmdTester->getDisplay());
        self::assertStringNotContainsString("App\Controller\ProductsController has 1 violations", $cmdTester->getDisplay());
    }

    public function test_does_not_explode_if_an_exception_is_thrown(): void
    {
        $cmdTester = $this->runCheck(__DIR__.'/../_fixtures/configThrowsException.php');

        self::assertCommandExitedWithError($cmdTester);
    }

    public function test_run_command_with_success(): void
    {
        $cmdTester = $this->runCheck(__DIR__.'/../_fixtures/configMvcWithoutErrors.php');

        self::assertCommandWasSuccessful($cmdTester);
        self::assertStringNotContainsString('⚠️', $cmdTester->getDisplay());
    }

    public function test_parse_error_in_the_codebase(): void
    {
        $cmdTester = $this->runCheck(__DIR__.'/../_fixtures/configParseError.php');

        $expectedErrors = <<<'ERRORS'
        Syntax error, unexpected T_STRING, expecting '{' on line 8 in file: Services/CartService.php
        ERRORS;

        self::assertCommandExitedWithError($cmdTester);
        self::assertStringContainsString($expectedErrors, $cmdTester->getErrorOutput());
    }

    public function test_bug_yield(): void
    {
        $cmdTester = $this->runCheck(__DIR__.'/../_fixtures/configMvcForYieldBug.php');

        $expectedErrors = <<<'ERRORS'
        App\Controller\Foo has 1 violations
          should have a name that matches *Controller
        ERRORS;

        self::assertCommandExitedWithError($cmdTester);
        self::assertStringContainsString($expectedErrors, $cmdTester->getDisplay());
    }

    public function test_baseline(): void
    {
        $configFilePath = __DIR__.'/../_fixtures/configMvcForYieldBug.php';

        // Produce the baseline

        $this->runCheck($configFilePath, null, null, $this->customBaselineFilename);

        // Check it detects error if baseline is not used

        $cmdTester = $this->runCheck($configFilePath, null, null);

        self::assertCommandExitedWithError($cmdTester);

        // Check it ignores error if baseline is used

        $cmdTester = $this->runCheck($configFilePath, null, $this->customBaselineFilename);

        self::assertCommandWasSuccessful($cmdTester);
    }

    public function test_baseline_with_default_filename_is_enabled_automatically(): void
    {
        $configFilePath = __DIR__.'/../_fixtures/configMvcForYieldBug.php';

        // Produce the baseline

        $this->runCheck($configFilePath, null, null, null);

        // Check it ignores error if baseline is used

        $cmdTester = $this->runCheck($configFilePath, null, null);

        self::assertCommandWasSuccessful($cmdTester);
    }

    public function test_you_can_ignore_the_default_baseline(): void
    {
        $configFilePath = __DIR__.'/../_fixtures/configMvcForYieldBug.php';

        // Produce the baseline
        $this->runCheck($configFilePath, null, null, null);

        // Check it ignores the default baseline
        $cmdTester = $this->runCheck($configFilePath, null, null, false, true);

        self::assertCommandExitedWithError($cmdTester);
    }

    public function test_dependencies_should_not_leak_between_files(): void
    {
        $cmdTester = $this->runCheck(__DIR__.'/../_fixtures/configDependenciesLeak.php');

        self::assertCommandWasSuccessful($cmdTester);
    }

    public function test_baseline_line_numbers_can_be_ignored(): void
    {
        $configFilePath = __DIR__.'/../_fixtures/configIgnoreBaselineLineNumbers.php';

        // No errors when ignoring baseline line numbers
        $cmdTester = $this->runCheck($configFilePath, null, __DIR__.'/../_fixtures/line_numbers/baseline.json', false, false, true);
        self::assertCommandWasSuccessful($cmdTester);

        // Errors when not ignoring baseline line numbers
        $cmdTester = $this->runCheck($configFilePath, null, __DIR__.'/../_fixtures/line_numbers/baseline.json');
        self::assertCommandExitedWithError($cmdTester);
    }

    public function test_json_format_output_errors(): void
    {
        $configFilePath = __DIR__.'/../_fixtures/configMvcForYieldBug.php';

        $cmdTester = $this->runCheck($configFilePath, null, null, false, false, false, 'json');

        $expectedJson = <<<'ERRORS'
        {
            "totalViolations": 1,
            "details": {
                "App\\Controller\\Foo": [
                    {
                        "error": "should have a name that matches *Controller because all controllers should be end name with Controller"
                    }
                ]
            }
        }
        ERRORS;

        self::assertCommandExitedWithError($cmdTester);
        self::assertJsonStringEqualsJsonString($expectedJson, $cmdTester->getDisplay());
    }

    public function test_json_format_output_no_errors(): void
    {
        $configFilePath = __DIR__.'/../_fixtures/configMvcWithoutErrors.php';

        $cmdTester = $this->runCheck($configFilePath, null, null, false, false, false, 'json');

        $expectedJson = '{"totalViolations":0,"details":[]}';

        self::assertCommandWasSuccessful($cmdTester);
        self::assertJsonStringEqualsJsonString($expectedJson, $cmdTester->getDisplay());
    }

    public function test_gitlab_format_output_errors(): void
    {
        $configFilePath = __DIR__.'/../_fixtures/configMvcForYieldBug.php';

        $cmdTester = $this->runCheck($configFilePath, null, null, false, false, false, 'gitlab');

        $expectedJson = <<<'ERRORS'
        [
            {
                "description": "should have a name that matches *Controller because all controllers should be end name with Controller",
                "check_name": "App\\Controller\\Foo.should-have-a-name-that-matches-controller-because-all-controllers-should-be-end-name-with-controller",
                "fingerprint": "1e960c3f49b5ec63ece40321072ef2bd0bc33ad11b7be326f304255d277dc860",
                "severity": "major",
                "location": {
                    "path": "Controller\/Foo.php",
                    "lines": {
                        "begin": 1
                    }
                }
            }
        ]
        ERRORS;

        self::assertCommandExitedWithError($cmdTester);
        self::assertJsonStringEqualsJsonString($expectedJson, $cmdTester->getDisplay());
    }

    public function test_gitlab_format_output_no_errors(): void
    {
        $configFilePath = __DIR__.'/../_fixtures/configMvcWithoutErrors.php';

        $cmdTester = $this->runCheck($configFilePath, null, null, false, false, false, 'gitlab');

        $expectedJson = '[]';

        self::assertCommandWasSuccessful($cmdTester);
        self::assertJsonStringEqualsJsonString($expectedJson, $cmdTester->getDisplay());
    }

    public function test_autoload_file(): void
    {
        $configFilePath = __DIR__.'/../_fixtures/autoload/phparkitect.php';

        $cmdTester = $this->runCheck($configFilePath, null, null, false, false, false, 'text', __DIR__.'/../_fixtures/autoload/autoload.php');

        self::assertCommandWasSuccessful($cmdTester);
    }

    protected function runCheck(
        $configFilePath = null,
        ?bool $stopOnFailure = null,
        ?string $useBaseline = null,
        $generateBaseline = false,
        bool $skipBaseline = false,
        bool $ignoreBaselineNumbers = false,
        string $format = 'text',
        ?string $autoloadFilePath = null,
    ): ApplicationTester {
        $input = ['check'];

        if (null !== $configFilePath) {
            $input['--config'] = $configFilePath;
        }
        if (null !== $stopOnFailure) {
            $input['--stop-on-failure'] = true;
        }
        if (null !== $useBaseline) {
            $input['--use-baseline'] = $useBaseline;
        }
        if ($skipBaseline) {
            $input['--skip-baseline'] = true;
        }

        if ($ignoreBaselineNumbers) {
            $input['--ignore-baseline-linenumbers'] = true;
        }

        // false = option not set, null = option set but without value, string = option with value
        if (false !== $generateBaseline) {
            $input['--generate-baseline'] = $generateBaseline;
        }

        $input['--format'] = $format;

        if ($autoloadFilePath) {
            $input['--autoload'] = $autoloadFilePath;
        }

        $app = new PhpArkitectApplication();
        $app->setAutoExit(false);

        $appTester = new ApplicationTester($app);
        $appTester->run($input, ['capture_stderr_separately' => true]);

        return $appTester;
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
