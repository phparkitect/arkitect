<?php

declare(strict_types=1);

namespace Arkitect\Tests\E2E;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class CliTest extends TestCase
{
    const SUCCESS_CODE = 0;

    const ERROR_CODE = 1;

    /** @var string */
    private $phparkitect = __DIR__.'/../../phparkitect';

    /** @var string */
    private $configWithErrors = __DIR__.'/fixtures/configMvc.php';

    /** @var string */
    private $configWithoutErrors = __DIR__.'/fixtures/configMvcWithoutErrors.php';

    public function test_returns_error(): void
    {
        $process = new Process([$this->phparkitect, 'check', '--config='.$this->configWithErrors], __DIR__);
        $process->run();
        $this->assertEquals(self::ERROR_CODE, $process->getExitCode());

        $expectedErrors = 'ERRORS!
App\Controller\Foo implements ContainerAwareInterface
App\Controller\Foo has a name that matches *Controller
App\Controller\ProductsController implements ContainerAwareInterface
App\Controller\UserController implements ContainerAwareInterface';
        $this->assertStringContainsString($expectedErrors, $process->getOutput());
    }

    public function test_run_command_with_success(): void
    {
        $process = new Process([$this->phparkitect, 'check', '--config='.$this->configWithoutErrors], __DIR__);
        $process->run();
        $this->assertEquals(self::SUCCESS_CODE, $process->getExitCode());

        $expectedOutput = 'ERRORS!';
        $this->assertStringNotContainsString($expectedOutput, $process->getOutput());
    }
}
