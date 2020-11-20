<?php
declare(strict_types=1);

namespace Arkitect\Tests\E2E;

use Arkitect\ClassSet;
use Arkitect\Expression\Implement;
use Arkitect\Expression\ResideInNamespace;
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
        $this->expectExceptionMessage(
            "Failed asserting that App\\Controller\\ProductsController does not implement ContainerAwareInterface\nApp\\Controller\\UserController does not implement ContainerAwareInterface."
        );

        ArchRuleTestCase::assertArchRule($rule, $set);
    }
}
