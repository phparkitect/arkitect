<?php

declare(strict_types=1);

namespace Arkitect\Tests\E2E;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class CliTest extends TestCase
{
    const SUCCESS_CODE = 0;

    const ERROR_CODE = 1;

    private string $phparkitect = __DIR__.'/../../bin-stub/phparkitect';

    public function test_returns_error_wit_mulitple_violations(): void
    {
        $process = $this->runArkitect(__DIR__.'/fixtures/configMvc.php');

        $expectedErrors = 'ERRORS!

App\Controller\Foo violates rules
should implement ContainerAwareInterface
should have a name that matches *Controller

App\Controller\ProductsController violates rules
should implement ContainerAwareInterface

App\Controller\UserController violates rules
should implement ContainerAwareInterface

App\Controller\YieldController violates rules
should implement ContainerAwareInterface

App\Domain\Model violates rules
should not depend on classes outside in namespace App\Domain
should not depend on classes outside in namespace App\Domain';

        $this->assertEquals(self::ERROR_CODE, $process->getExitCode());
        $this->assertStringContainsString($expectedErrors, $process->getOutput());
    }

    public function test_does_not_explode_if_an_exception_is_thrown(): void
    {
        $process = $this->runArkitect(__DIR__.'/fixtures/configThrowsException.php');

        $this->assertEquals(self::ERROR_CODE, $process->getExitCode());
    }

    public function test_run_command_with_success(): void
    {
        $process = $this->runArkitect(__DIR__.'/fixtures/configMvcWithoutErrors.php');

        $this->assertEquals(self::SUCCESS_CODE, $process->getExitCode());
        $this->assertStringNotContainsString('ERRORS!', $process->getOutput());
    }

    public function test_bug_yield(): void
    {
        $process = $this->runArkitect(__DIR__.'/fixtures/configMvcForYieldBug.php');

        $expectedErrors = 'ERRORS!

App\Controller\Foo violates rules
should have a name that matches *Controller';

        $this->assertEquals(self::ERROR_CODE, $process->getExitCode());
        $this->assertStringContainsString($expectedErrors, $process->getOutput());
    }

    protected function runArkitect($configFilePath): Process
    {
        $process = new Process([$this->phparkitect, 'check', '--config='.$configFilePath], __DIR__);
        $process->run();

        return $process;
    }
}
