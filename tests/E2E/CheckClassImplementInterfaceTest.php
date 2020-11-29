<?php
declare(strict_types=1);

namespace Arkitect\Tests\E2E;

use Arkitect\ClassSet;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Expression\ForClasses\ResideInNamespace;
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

        $rule = Rule::allClasses()
            ->that(new ResideInNamespace('App\Controller'))
            ->should(new Implement('ContainerAwareInterface'))
            ->because('i said so');

        $expectedExceptionMessage = <<< 'EOT'
            Failed asserting that 
            App\Controller\ProductsController implements ContainerAwareInterface
            App\Controller\UserController implements ContainerAwareInterface.
            EOT;

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        ArchRuleTestCase::assertArchRule($rule, $set);
    }
}
