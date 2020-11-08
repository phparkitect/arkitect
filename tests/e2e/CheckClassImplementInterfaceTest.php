<?php
declare(strict_types=1);

namespace ArkitectTests;

use Arkitect\ClassSet;
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
            ->that()
                ->resideInNamespace('App\Controller')
            ->should()
                ->implement('ContainerAwareInterface')
            ->get();

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("Failed asserting that App\Controller\UserController does not implement ContainerAwareInterface
App\Controller\ProductsController does not implement ContainerAwareInterface.");

        ArchRuleTestCase::assertArchRule($rule, $set);
    }
}
