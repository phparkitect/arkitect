<?php

declare(strict_types=1);

namespace Arkitect\Tests\E2E\Cli;

use Arkitect\CLI\PhpArkitectApplication;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\ApplicationTester;

class DebugExpressionCommandTest extends TestCase
{
    public function test_you_need_to_specify_the_expression(): void
    {
        $appTester = $this->createAppTester();
        $appTester->run(['debug:expression']);
        $this->assertEquals(1, $appTester->getStatusCode());
    }

    public function test_zero_results(): void
    {
        $appTester = $this->createAppTester();
        $appTester->run(['debug:expression', 'expression' => 'Extend', 'arguments' => ['NotFound'], '--from-dir' => __DIR__]);
        $this->assertEquals('', $appTester->getDisplay());
        $this->assertEquals(0, $appTester->getStatusCode());
    }

    public function test_some_classes_found(): void
    {
        $appTester = $this->createAppTester();
        $appTester->run(['debug:expression', 'expression' => 'NotExtend', 'arguments' => ['NotFound'], '--from-dir' => __DIR__.'/../_fixtures/mvc/Domain']);
        $this->assertEquals("App\Domain\Model\n", $appTester->getDisplay());
        $this->assertEquals(0, $appTester->getStatusCode());
    }

    public function test_meaningful_errors_for_too_few_arguments_for_the_expression(): void
    {
        $appTester = $this->createAppTester();
        $appTester->run(['debug:expression', 'expression' => 'NotImplement', 'arguments' => [], '--from-dir' => __DIR__.'/../_fixtures/mvc/Domain']);
        $this->assertEquals("Error: Too few arguments for 'NotImplement'.\n", $appTester->getDisplay());
        $this->assertEquals(2, $appTester->getStatusCode());
    }

    public function test_meaningful_errors_for_too_many_arguments_for_the_expression(): void
    {
        $appTester = $this->createAppTester();
        $appTester->run(['debug:expression', 'expression' => 'NotImplement', 'arguments' => ['First', 'Second'], '--from-dir' => __DIR__.'/../_fixtures/mvc/Domain']);
        $this->assertEquals("Error: Too many arguments for 'NotImplement'.\n", $appTester->getDisplay());
        $this->assertEquals(2, $appTester->getStatusCode());
    }

    public function test_optional_argument_for_expression_can_be_avoided(): void
    {
        $appTester = $this->createAppTester();
        $appTester->run(['debug:expression', 'expression' => 'NotHaveDependencyOutsideNamespace', 'arguments' => ['NotFound'], '--from-dir' => __DIR__]);
        $this->assertEquals('', $appTester->getDisplay());
        $this->assertEquals(0, $appTester->getStatusCode());
    }

    public function test_expression_not_found(): void
    {
        $appTester = $this->createAppTester();
        $appTester->run(['debug:expression', 'expression' => 'blabla', 'arguments' => ['NotFound'], '--from-dir' => __DIR__]);
        $this->assertEquals("Error: Expression 'blabla' not found.\n", $appTester->getDisplay());
        $this->assertEquals(2, $appTester->getStatusCode());
    }

    public function test_parse_error_dont_stop_execution(): void
    {
        $appTester = $this->createAppTester();
        $appTester->run(['debug:expression', 'expression' => 'NotExtend', 'arguments' => ['NotFound'], '--from-dir' => __DIR__.'/../_fixtures/parse_error']);
        $errorMessage = <<<END
ContainerAwareInterface
WARNING: Some files could not be parsed for these errors:
 - Syntax error, unexpected T_STRING, expecting '{' on line 8: Services/CartService.php

App\Services\UserService

END;
        $this->assertEquals($errorMessage, $appTester->getDisplay());
        $this->assertEquals(0, $appTester->getStatusCode());
    }

    private function createAppTester(): ApplicationTester
    {
        $app = new PhpArkitectApplication();
        $app->setAutoExit(false);

        return new ApplicationTester($app);
    }
}
