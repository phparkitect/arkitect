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

    /** @var string */
    private $baselineFilename = 'my-baseline.json';

    protected function tearDown(): void
    {
        if (file_exists($this->baselineFilename)) {
            unlink($this->baselineFilename);
        }
    }

    public function test_app_returns_error_with_multiple_violations(): void
    {
        $cmdTester = $this->runCheck(__DIR__.'/../_fixtures/configMvc.php');

        $expectedErrors = 'ERRORS!

App\Controller\Foo has 2 violations
  should implement ContainerAwareInterface because all controllers should be container aware
  should have a name that matches *Controller because we want uniform naming

App\Controller\ProductsController has 1 violations
  should implement ContainerAwareInterface because all controllers should be container aware

App\Controller\UserController has 1 violations
  should implement ContainerAwareInterface because all controllers should be container aware

App\Controller\YieldController has 1 violations
  should implement ContainerAwareInterface because all controllers should be container aware

App\Domain\Model has 2 violations
  depends on App\Services\UserService, but should not depend on classes outside namespace App\Domain because we want protect our domain (on line 14)
  depends on App\Services\CartService, but should not depend on classes outside namespace App\Domain because we want protect our domain (on line 15)';

        $this->assertCheckHasErrors($cmdTester, $expectedErrors);
    }

    public function test_app_returns_single_error_because_there_is_stop_on_failure_param(): void
    {
        $cmdTester = $this->runCheck(__DIR__.'/../_fixtures/configMvc.php', true);

        $expectedErrors = 'ERRORS!
App\Controller\Foo has 1 violations
  should implement ContainerAwareInterface because all controllers should be container aware';

        $this->assertCheckHasErrors($cmdTester, $expectedErrors);
        $this->assertCheckHasNoErrorsLike($cmdTester, "App\Controller\ProductsController has 1 violations");
    }

    public function test_does_not_explode_if_an_exception_is_thrown(): void
    {
        $cmdTester = $this->runCheck(__DIR__.'/../_fixtures/configThrowsException.php');

        $this->assertCheckHasErrors($cmdTester);
    }

    public function test_run_command_with_success(): void
    {
        $cmdTester = $this->runCheck(__DIR__.'/../_fixtures/configMvcWithoutErrors.php');

        $this->assertCheckHasSuccess($cmdTester);
    }

    public function test_parse_error_in_the_codebase(): void
    {
        $cmdTester = $this->runCheck(__DIR__.'/../_fixtures/configParseError.php');

        $expectedErrors = "ERROR ON PARSING THESE FILES:Syntax error, unexpected T_STRING, expecting '{' on line 8 in file: Services/CartService.php";
        $this->assertCheckHasErrors($cmdTester, $expectedErrors);
    }

    public function test_bug_yield(): void
    {
        $cmdTester = $this->runCheck(__DIR__.'/../_fixtures/configMvcForYieldBug.php');

        $expectedErrors = 'ERRORS!

App\Controller\Foo has 1 violations
  should have a name that matches *Controller';

        $this->assertCheckHasErrors($cmdTester, $expectedErrors);
    }

    public function test_baseline(): void
    {
        $configFilePath = __DIR__.'/../_fixtures/configMvcForYieldBug.php';

        // Produce the baseline

        $this->runCheck($configFilePath, null, null, $this->baselineFilename);

        // Check it detects error if baseline is not used

        $cmdTester = $this->runCheck($configFilePath, null, null, null);

        $this->assertCheckHasErrors($cmdTester);

        // Check it ignores error if baseline is used

        $cmdTester = $this->runCheck($configFilePath, null, $this->baselineFilename);

        $this->assertCheckHasSuccess($cmdTester);
    }

    protected function runCheck(
        $configFilePath = null,
        bool $stopOnFailure = null,
        ?string $useBaseline = null,
        ?string $setBaseline = null
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
        if (null !== $setBaseline) {
            $input['--set-baseline'] = $setBaseline;
        }

        $app = new PhpArkitectApplication();
        $app->setAutoExit(false);

        $appTester = new ApplicationTester($app);
        $appTester->run($input);

        return $appTester;
    }

    protected function assertCheckHasErrors(ApplicationTester $applicationTester, string $expectedOutput = null): void
    {
        $this->assertEquals(self::ERROR_CODE, $applicationTester->getStatusCode());
        if (null != $expectedOutput) {
            $actualOutput = str_replace(["\r", "\n"], '', $applicationTester->getDisplay());
            $expectedOutput = str_replace(["\r", "\n"], '', $expectedOutput);
            $this->assertStringContainsString($expectedOutput, $actualOutput);
        }
    }

    protected function assertCheckHasNoErrorsLike(ApplicationTester $applicationTester, string $expectedOutput = null): void
    {
        $this->assertEquals(self::ERROR_CODE, $applicationTester->getStatusCode());
        if (null != $expectedOutput) {
            $actualOutput = str_replace(["\r", "\n"], '', $applicationTester->getDisplay());
            $expectedOutput = str_replace(["\r", "\n"], '', $expectedOutput);
            $this->assertStringNotContainsString($expectedOutput, $actualOutput);
        }
    }

    protected function assertCheckHasSuccess(ApplicationTester $applicationTester): void
    {
        $this->assertEquals(self::SUCCESS_CODE, $applicationTester->getStatusCode(), 'Error code not expected in successful execution');
        $this->assertStringNotContainsString('ERRORS!', $applicationTester->getDisplay(), 'Error message not expected in successful execution');
    }
}
