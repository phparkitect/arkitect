<?php

declare(strict_types=1);

namespace Arkitect\Tests\Integration;

use Arkitect\Expression\ForClasses\Extend;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Rules\Rule;
use Arkitect\Tests\Utils\TestRunner;
use PHPUnit\Framework\TestCase;

class ExtendsThrowableTest extends TestCase
{
    public function test_naming_is_enforced(): void
    {
        $dir = __DIR__.'/Fixtures/ExtendsThrowable';

        $runner = TestRunner::create('8.2');

        $rule = Rule::allClasses()
            ->that(new Extend(\RuntimeException::class))
            ->should(new HaveNameMatching('*Exception'))
            ->because('reasons');

        $runner->run($dir, $rule);

        self::assertCount(1, $runner->getViolations());
        self::assertCount(0, $runner->getParsingErrors());

        self::assertStringContainsString('should have a name that matches *Exception because', $runner->getViolations()->get(0)->getError());
    }
}
