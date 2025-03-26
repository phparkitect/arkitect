<?php
declare(strict_types=1);

namespace Arkitect\Tests\E2E\PHPUnit;

use Arkitect\ClassSet;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * @group e2e
 */
class CheckClassImplementInterfaceTest extends TestCase
{
    public function test_assertion_should_fail_on_broken_rule(): void
    {
        $set = ClassSet::fromDir(__DIR__.'/../_fixtures/mvc');

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Controller', 'App\Services'))
            ->should(new Implement('ContainerAwareInterface'))
            ->because('i said so');

        $expectedExceptionMessage = '
App\Controller\Foo has 1 violations
  should implement ContainerAwareInterface because i said so

App\Controller\ProductsController has 1 violations
  should implement ContainerAwareInterface because i said so

App\Controller\UserController has 1 violations
  should implement ContainerAwareInterface because i said so

App\Controller\YieldController has 1 violations
  should implement ContainerAwareInterface because i said so

App\Services\UserService has 1 violations
  should implement ContainerAwareInterface because i said so';

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        ArchRuleTestCase::assertArchRule($rule, $set);
    }

    public function test_assertion_should_fail_on_parser_errors(): void
    {
        $set = ClassSet::fromDir(__DIR__.'/../_fixtures/parse_error');

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Controller', 'App\Services'))
            ->should(new Implement('ContainerAwareInterface'))
            ->because('i said so');

        $expectedExceptionMessage = "Syntax error, unexpected T_STRING, expecting '{' on line 8 in file: Services/CartService.php";

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        ArchRuleTestCase::assertArchRule($rule, $set);
    }
}
