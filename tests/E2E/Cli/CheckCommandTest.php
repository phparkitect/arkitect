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
  should not depend on classes outside in namespace App\Domain (on line 14)
  should not depend on classes outside in namespace App\Domain (on line 15)

App\View\UserView violates rules
  should implement AbstractView';

        $this->assertEquals(self::ERROR_CODE, $cmdTester->getStatusCode());
        $this->assertStringContainsString($expectedErrors, $cmdTester->getDisplay());
    }

    public function test_does_not_explode_if_an_exception_is_thrown(): void
    {
        $cmdTester = $this->runCheck(__DIR__.'/../_fixtures/configThrowsException.php');

        $this->assertEquals(self::ERROR_CODE, $cmdTester->getStatusCode());
    }

    public function test_run_command_with_success(): void
    {
        $cmdTester = $this->runCheck(__DIR__.'/../_fixtures/configMvcWithoutErrors.php');

        $this->assertEquals(self::SUCCESS_CODE, $cmdTester->getStatusCode());
        $this->assertStringNotContainsString('ERRORS!', $cmdTester->getDisplay());
    }

    public function test_bug_yield(): void
    {
        $cmdTester = $this->runCheck(__DIR__.'/../_fixtures/configMvcForYieldBug.php');

        $expectedErrors = 'ERRORS!

App\Controller\Foo violates rules
  should have a name that matches *Controller';

        $this->assertEquals(self::ERROR_CODE, $cmdTester->getStatusCode());
        $this->assertStringContainsString($expectedErrors, $cmdTester->getDisplay());
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
}
