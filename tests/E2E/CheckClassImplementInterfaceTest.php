<?php
declare(strict_types=1);

namespace Arkitect\Tests\E2E;

use Arkitect\ClassSet;
use Arkitect\DSL\Rule;
use Arkitect\Expression\ImplementInterface;
use Arkitect\Expression\ResideInNamespace;
use Arkitect\PHPUnit\ArchRuleTestCase;
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
            ->should(new ImplementInterface('ContainerAwareInterface'))
            ->because('Of some reason');

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage("Failed asserting that App\Controller\UserController implements ContainerAwareInterface");

        ArchRuleTestCase::assertArchRule($rule, $set);
    }
}
