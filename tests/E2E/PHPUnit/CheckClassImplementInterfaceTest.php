<?php
declare(strict_types=1);

namespace Arkitect\Tests\E2E\PHPUnit;

use Arkitect\ClassSet;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\PHPUnit\ArchRuleTestCase;
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
        $set = ClassSet::fromDir(__DIR__.'/../Fixtures/MvcExample');

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('Arkitect\Tests\E2E\Fixtures\MvcExample\Controller', 'Arkitect\Tests\E2E\Fixtures\MvcExample\Services'))
            ->should(new Implement('Arkitect\Tests\E2E\Fixtures\MvcExample\ContainerAwareInterface'))
            ->because('i said so');

        $expectedExceptionMessage = '
Arkitect\Tests\E2E\Fixtures\MvcExample\Controller\BaseController violates rules
  should implement Arkitect\Tests\E2E\Fixtures\MvcExample\ContainerAwareInterface

Arkitect\Tests\E2E\Fixtures\MvcExample\Controller\Foo violates rules
  should implement Arkitect\Tests\E2E\Fixtures\MvcExample\ContainerAwareInterface

Arkitect\Tests\E2E\Fixtures\MvcExample\Controller\JsonController violates rules
  should implement Arkitect\Tests\E2E\Fixtures\MvcExample\ContainerAwareInterface

Arkitect\Tests\E2E\Fixtures\MvcExample\Controller\ProductsController violates rules
  should implement Arkitect\Tests\E2E\Fixtures\MvcExample\ContainerAwareInterface

Arkitect\Tests\E2E\Fixtures\MvcExample\Controller\UserController violates rules
  should implement Arkitect\Tests\E2E\Fixtures\MvcExample\ContainerAwareInterface

Arkitect\Tests\E2E\Fixtures\MvcExample\Controller\YieldController violates rules
  should implement Arkitect\Tests\E2E\Fixtures\MvcExample\ContainerAwareInterface

Arkitect\Tests\E2E\Fixtures\MvcExample\Services\UserService violates rules
  should implement Arkitect\Tests\E2E\Fixtures\MvcExample\ContainerAwareInterface';

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        ArchRuleTestCase::assertArchRule($rule, $set);
    }
}
