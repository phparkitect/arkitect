<?php

declare(strict_types=1);

namespace Arkitect\Tests\Integration;

use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Rules\Rule;
use Arkitect\Tests\Integration\Fixtures\Implements\AnInterface;
use Arkitect\Tests\Utils\TestRunner;
use PHPUnit\Framework\TestCase;

class ImplementsTest extends TestCase
{
    public function test_naming_is_enforced(): void
    {
        $dir = __DIR__.'/Fixtures/Implements';

        $runner = TestRunner::create('8.2');

        $rule = Rule::allClasses()
            ->that(new Implement(AnInterface::class))
            ->should(new HaveNameMatching('An*'))
            ->because('reasons');

        $runner->run($dir, $rule);

        self::assertCount(0, $runner->getParsingErrors());
        self::assertCount(1, $runner->getViolations());

        self::assertEquals(
            'Arkitect\Tests\Integration\Fixtures\Implements\AClass',
            $runner->getViolations()->get(0)->getFqcn()
        );
        self::assertStringContainsString(
            'should have a name that matches An* because reasons',
            $runner->getViolations()->get(0)->getError()
        );
    }
}
