<?php
declare(strict_types=1);

namespace ArkitectTests;

use Arkitect\ClassSet;
use Arkitect\PHPUnit\ArchRuleTestCase;
use Arkitect\Rules\ArchRule;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * @group e2e
 */
class CheckClassImplementInterfaceTest extends TestCase
{
    public function test_assertion_should_fail_on_broken_rule(): void
    {
        $set = ClassSet::fromDir(__DIR__ . '/fixtures/mvc');

        $rule = ArchRule::classes()
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

    public function test_assertion_should_fail_on_broken_rule_woth_excluded_files(): void
    {
        $set = ClassSet::fromDir(__DIR__ . '/fixtures/mvc');

        $rule = ArchRule::classes()
            ->that()
            ->resideInNamespace('App\Controller')
            ->should()
            ->implement('ContainerAwareInterface')
            ->get()
            ->excludeFiles(['App\Controller\ProductsController', 'App\Controller\UserController']);

        ArchRuleTestCase::assertArchRule($rule, $set);
    }
}
