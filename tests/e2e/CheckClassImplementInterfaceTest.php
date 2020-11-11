<?php
declare(strict_types=1);

namespace ArkitectTests;

use Arkitect\ClassSet;
use Arkitect\Constraints\Implement;
use Arkitect\Constraints\ResideInNamespace;
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
        $set = ClassSet::fromDir(__DIR__.'/fixtures/mvc');

        $rule = Rule::classes()
            ->that(new ResideInNamespace('App\Controller'))
            ->should(new Implement('ContainerAwareInterface'));

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("Failed asserting that App\Controller\UserController does not implement ContainerAwareInterface
App\Controller\ProductsController does not implement ContainerAwareInterface.");

        ArchRuleTestCase::assertArchRule($rule, $set);
    }
}
