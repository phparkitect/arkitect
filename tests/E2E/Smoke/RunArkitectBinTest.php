<?php

declare(strict_types=1);

namespace Arkitect\Tests\E2E;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class RunArkitectBinTest extends TestCase
{
    const SUCCESS_CODE = 0;

    const ERROR_CODE = 1;

    private string $phparkitect = __DIR__.'/../../../bin-stub/phparkitect';

    public function test_returns_error_with_multiple_violations(): void
    {
        $process = $this->runArkitectPassingConfigFilePath(__DIR__.'/../_fixtures/configMvc.php');

        $expectedErrors = '

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
  depends on App\Services\CartService, but should not depend on classes outside namespace App\Domain because we want protect our domain (on line 15)';

        self::assertEquals(self::ERROR_CODE, $process->getExitCode());
        self::assertStringContainsString($expectedErrors, $process->getOutput());
    }

    public function test_returns_error_with_multiple_violations_without_passing_config_file(): void
    {
        $process = $this->runArkitect();

        $expectedErrors = '

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
  depends on App\Services\CartService, but should not depend on classes outside namespace App\Domain because we want protect our domain (on line 15)';

        self::assertStringContainsString($expectedErrors, $process->getOutput());
        self::assertEquals(self::ERROR_CODE, $process->getExitCode());
    }

    public function test_does_not_explode_if_an_exception_is_thrown(): void
    {
        $process = $this->runArkitectPassingConfigFilePath(__DIR__.'/../_fixtures/configThrowsException.php');

        self::assertEquals(self::ERROR_CODE, $process->getExitCode());
    }

    public function test_run_command_with_success(): void
    {
        $process = $this->runArkitectPassingConfigFilePath(__DIR__.'/../_fixtures/configMvcWithoutErrors.php');

        self::assertEquals(self::SUCCESS_CODE, $process->getExitCode());
        self::assertStringNotContainsString('⚠️', $process->getOutput());
    }

    public function test_bug_yield(): void
    {
        $process = $this->runArkitectPassingConfigFilePath(__DIR__.'/../_fixtures/configMvcForYieldBug.php');

        $expectedErrors = '

App\Controller\Foo has 1 violations
  should have a name that matches *Controller';

        self::assertEquals(self::ERROR_CODE, $process->getExitCode());
        self::assertStringContainsString($expectedErrors, $process->getOutput());
    }

    public function test_only_violations_are_printed_on_stdout(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'format_json');
        $binPath = $this->phparkitect;
        $configFilePath = __DIR__.'/../_fixtures/configMvc.php';

        $process = Process::fromShellCommandline("php {$binPath} check --config=$configFilePath --format=gitlab > $tmpFile");
        $process->run();

        $fileContent = file_get_contents($tmpFile);

        self::assertJson($fileContent);
    }

    protected function runArkitectPassingConfigFilePath($configFilePath): Process
    {
        $process = new Process([$this->phparkitect, 'check', '--config='.$configFilePath], __DIR__);
        $process->run();

        return $process;
    }

    protected function runArkitect(): Process
    {
        $process = new Process([$this->phparkitect, 'check'], __DIR__);
        $process->run();

        return $process;
    }
}
