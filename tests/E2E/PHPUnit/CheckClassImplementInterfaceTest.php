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
App\Controller\Foo violates rules
  should implement ContainerAwareInterface because i said so

App\Controller\ProductsController violates rules
  should implement ContainerAwareInterface because i said so

App\Controller\UserController violates rules
  should implement ContainerAwareInterface because i said so

App\Controller\YieldController violates rules
  should implement ContainerAwareInterface because i said so

App\Services\UserService violates rules
  should implement ContainerAwareInterface because i said so';

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        ArchRuleTestCase::assertArchRule($rule, $set);
    }
}
