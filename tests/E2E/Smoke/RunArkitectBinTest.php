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
        $process = $this->runArkitectPassingConfigFilePath(__DIR__.'/../Fixtures/configMvc.php');

        $expectedErrors = 'ERRORS!

Arkitect\Tests\E2E\Fixtures\MvcExample\Controller\Foo violates rules
  should implement Arkitect\Tests\E2E\Fixtures\MvcExample\ContainerAwareInterface
  should have a name that matches *Controller

Arkitect\Tests\E2E\Fixtures\MvcExample\Controller\JsonController violates rules
  should implement Arkitect\Tests\E2E\Fixtures\MvcExample\ContainerAwareInterface

Arkitect\Tests\E2E\Fixtures\MvcExample\Controller\ProductsController violates rules
  should implement Arkitect\Tests\E2E\Fixtures\MvcExample\ContainerAwareInterface

Arkitect\Tests\E2E\Fixtures\MvcExample\Controller\UserController violates rules
  should implement Arkitect\Tests\E2E\Fixtures\MvcExample\ContainerAwareInterface

Arkitect\Tests\E2E\Fixtures\MvcExample\Controller\YieldController violates rules
  should implement Arkitect\Tests\E2E\Fixtures\MvcExample\ContainerAwareInterface

Arkitect\Tests\E2E\Fixtures\MvcExample\Domain\Model violates rules
  should not depend on classes outside namespace Arkitect\Tests\E2E\Fixtures\MvcExample\Domain (on line 13';

        $this->assertEquals(self::ERROR_CODE, $process->getExitCode());
        $this->assertStringContainsString($expectedErrors, $process->getOutput());
    }

    public function test_does_not_explode_if_an_exception_is_thrown(): void
    {
        $process = $this->runArkitectPassingConfigFilePath(__DIR__.'/../Fixtures/configThrowsException.php');

        $this->assertEquals(self::ERROR_CODE, $process->getExitCode());
    }

    public function test_run_command_with_success(): void
    {
        $process = $this->runArkitectPassingConfigFilePath(__DIR__.'/../Fixtures/configMvcWithoutErrors.php');

        $this->assertEquals(self::SUCCESS_CODE, $process->getExitCode());
        $this->assertStringNotContainsString('ERRORS!', $process->getOutput());
    }

    public function test_bug_yield(): void
    {
        $process = $this->runArkitectPassingConfigFilePath(__DIR__.'/../Fixtures/configMvcForYieldBug.php');

        $expectedErrors = 'ERRORS!

Arkitect\Tests\E2E\Fixtures\MvcExample\Controller\Foo violates rules
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
