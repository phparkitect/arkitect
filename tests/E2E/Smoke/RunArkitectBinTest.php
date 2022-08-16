<?php

declare(strict_types=1);

namespace Arkitect\Tests\E2E;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class RunArkitectBinTest extends TestCase
{
    const SUCCESS_CODE = 0;

    const ERROR_CODE = 1;

    /** @var string */
    private $phparkitect = __DIR__.'/../../../bin-stub/phparkitect';

    public function test_returns_error_with_multiple_violations(): void
    {
        $process = $this->runArkitectPassingConfigFilePath(__DIR__.'/../_fixtures/configMvc.php');

        $expectedErrors = 'ERRORS!

App\Controller\Foo violates rules
  should implement ContainerAwareInterface because all controllers should be container aware
  should have a name that matches *Controller because we want uniform naming

App\Controller\ProductsController violates rules
  should implement ContainerAwareInterface because all controllers should be container aware

App\Controller\UserController violates rules
  should implement ContainerAwareInterface because all controllers should be container aware

App\Controller\YieldController violates rules
  should implement ContainerAwareInterface because all controllers should be container aware

App\Domain\Model violates rules
  depends on App\Services\UserService, but should not depend on classes outside namespace App\Domain because we want protect our domain (on line 14)
  depends on App\Services\CartService, but should not depend on classes outside namespace App\Domain because we want protect our domain (on line 15)';

        $this->assertEquals(self::ERROR_CODE, $process->getExitCode());
        $this->assertStringContainsString($expectedErrors, $process->getOutput());
    }

    public function test_returns_error_with_multiple_violations_without_passing_config_file(): void
    {
        $process = $this->runArkitect();

        $expectedErrors = 'ERRORS!

App\Controller\Foo violates rules
  should implement ContainerAwareInterface because all controllers should be container aware
  should have a name that matches *Controller because we want uniform naming

App\Controller\ProductsController violates rules
  should implement ContainerAwareInterface because all controllers should be container aware

App\Controller\UserController violates rules
  should implement ContainerAwareInterface because all controllers should be container aware

App\Controller\YieldController violates rules
  should implement ContainerAwareInterface because all controllers should be container aware

App\Domain\Model violates rules
  depends on App\Services\UserService, but should not depend on classes outside namespace App\Domain because we want protect our domain (on line 14)
  depends on App\Services\CartService, but should not depend on classes outside namespace App\Domain because we want protect our domain (on line 15)';

        $this->assertStringContainsString($expectedErrors, $process->getOutput());
        $this->assertEquals(self::ERROR_CODE, $process->getExitCode());
    }

    public function test_does_not_explode_if_an_exception_is_thrown(): void
    {
        $process = $this->runArkitectPassingConfigFilePath(__DIR__.'/../_fixtures/configThrowsException.php');

        $this->assertEquals(self::ERROR_CODE, $process->getExitCode());
    }

    public function test_run_command_with_success(): void
    {
        $process = $this->runArkitectPassingConfigFilePath(__DIR__.'/../_fixtures/configMvcWithoutErrors.php');

        $this->assertEquals(self::SUCCESS_CODE, $process->getExitCode());
        $this->assertStringNotContainsString('ERRORS!', $process->getOutput());
    }

    public function test_bug_yield(): void
    {
        $process = $this->runArkitectPassingConfigFilePath(__DIR__.'/../_fixtures/configMvcForYieldBug.php');

        $expectedErrors = 'ERRORS!

App\Controller\Foo violates rules
  should have a name that matches *Controller';

        $this->assertEquals(self::ERROR_CODE, $process->getExitCode());
        $this->assertStringContainsString($expectedErrors, $process->getOutput());
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
