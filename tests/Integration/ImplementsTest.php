<?php

declare(strict_types=1);

namespace Arkitect\Tests\Integration;

use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Rules\Rule;
use Arkitect\Tests\Utils\TestRunner;
use PHPUnit\Framework\TestCase;

class ImplementsTest extends TestCase
{
    public function test_naming_is_enforced(): void
    {
        $fixturesDir = __DIR__.'/../Fixtures/Implements';

        $runner = TestRunner::create('8.2');

        $rule = Rule::allClasses()
            ->that(new Implement('Arkitect\Tests\Fixtures\Implements\AnInterface'))
            ->should(new HaveNameMatching('An*'))
            ->because('reasons');

        $runner->run($fixturesDir, $rule);

        self::assertCount(0, $runner->getParsingErrors());
        self::assertCount(2, $runner->getViolations());

        self::assertEquals('Arkitect\Tests\Fixtures\Implements\AClass', $runner->getViolations()->get(0)->getFqcn());
        self::assertStringContainsString('should have a name that matches An* because reasons', $runner->getViolations()->get(0)->getError());

        self::assertEquals('Arkitect\Tests\Fixtures\Implements\AEnum', $runner->getViolations()->get(1)->getFqcn());
        self::assertStringContainsString('should have a name that matches An* because reasons', $runner->getViolations()->get(1)->getError());
    }
}
