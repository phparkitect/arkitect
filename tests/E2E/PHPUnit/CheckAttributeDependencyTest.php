<?php

declare(strict_types=1);

namespace Arkitect\Tests\E2E\PHPUnit;

use Arkitect\ClassSet;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class CheckAttributeDependencyTest extends TestCase
{
    public function test_assertion_should_fail_on_invalid_dependency(): void
    {
        $set = ClassSet::fromDir(__DIR__.'/../_fixtures/attributes');

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\Service'))
            ->should(new NotDependsOnTheseNamespaces('App\Service\Invalid'))
            ->because('i said so');

        $expectedExceptionMessage = '
App\Service\Foo has 1 violations
  should not depend on these namespaces: App\Service\Invalid because i said so';

        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        ArchRuleTestCase::assertArchRule($rule, $set);
    }
}
