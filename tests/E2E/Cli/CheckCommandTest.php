<?php

declare(strict_types=1);

namespace Arkitect\Tests\E2E\Cli;

use Arkitect\CLI\Application;
use Arkitect\CLI\Check;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CheckCommandTest extends TestCase
{
    const SUCCESS_CODE = 0;

    const ERROR_CODE = 1;

    public function test_app_returns_error_with_multiple_violations(): void
    {
        $cmdTester = $this->runCheck(__DIR__.'/../_fixtures/configMvc.php');

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
  should not depend on classes outside namespace App\Domain (on line 14)
  should not depend on classes outside namespace App\Domain (on line 15)';

        $this->assertCheckHasErrors($cmdTester, $expectedErrors);
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

    public function test_bug_yield(): void
    {
        $cmdTester = $this->runCheck(__DIR__.'/../_fixtures/configMvcForYieldBug.php');

        $expectedErrors = 'ERRORS!

App\Controller\Foo violates rules
  should have a name that matches *Controller';

        $this->assertCheckHasErrors($cmdTester, $expectedErrors);
    }

    protected function runCheck($configFilePath = null): CommandTester
    {
        $input = $configFilePath ? ['--config' => $configFilePath] : [];

        $app = new Application('PHPArkitect', 'dunno');
        $app->add(new Check());

        $command = $app->find('check');

        $appTester = new CommandTester($command);
        $appTester->execute($input);

        return $appTester;
    }

    protected function assertCheckHasErrors(CommandTester $commandTester, string $expectedOutput = null): void
    {
        $this->assertEquals(self::ERROR_CODE, $commandTester->getStatusCode());
        if (null != $expectedOutput) {
            $actualOutput = str_replace(["\r", "\n"], '', $commandTester->getDisplay());
            $expectedOutput = str_replace(["\r", "\n"], '', $expectedOutput);
            $this->assertStringContainsString($expectedOutput, $actualOutput);
        }
    }

    protected function assertCheckHasSuccess(CommandTester $commandTester): void
    {
        $this->assertEquals(self::SUCCESS_CODE, $commandTester->getStatusCode());
        $this->assertStringNotContainsString('ERRORS!', $commandTester->getDisplay());
    }
}
