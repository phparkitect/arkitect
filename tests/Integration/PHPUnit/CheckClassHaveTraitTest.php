<?php

declare(strict_types=1);

namespace Arkitect\Tests\Integration\PHPUnit;

use Arkitect\Expression\ForClasses\HaveTrait;
use Arkitect\Expression\ForClasses\NotHaveTrait;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;
use Arkitect\Tests\Utils\TestRunner;
use PHPUnit\Framework\TestCase;

final class CheckClassHaveTraitTest extends TestCase
{
    public function test_feature_tests_should_use_database_transactions_trait(): void
    {
        $dir = __DIR__.'/Fixtures';

        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('Arkitect\Tests\Integration\PHPUnit\Fixtures\Feature'))
            ->should(new HaveTrait('DatabaseTransactions'))
            ->because('we want all Feature tests to run transactions');

        $runner->run($dir, $rule);

        self::assertCount(1, $runner->getViolations());
        self::assertCount(0, $runner->getParsingErrors());

        self::assertEquals(
            'Arkitect\Tests\Integration\PHPUnit\Fixtures\Feature\UserFeatureTest',
            $runner->getViolations()->get(0)->getFqcn()
        );
    }

    public function test_feature_tests_should_not_use_refresh_database_trait(): void
    {
        $dir = __DIR__.'/Fixtures';

        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('Arkitect\Tests\Integration\PHPUnit\Fixtures\Feature'))
            ->should(new NotHaveTrait('RefreshDatabase'))
            ->because('we want all Feature tests to never refresh the database for performance reasons');

        $runner->run($dir, $rule);

        self::assertCount(1, $runner->getViolations());
        self::assertCount(0, $runner->getParsingErrors());

        self::assertEquals(
            'Arkitect\Tests\Integration\PHPUnit\Fixtures\Feature\ProductFeatureTest',
            $runner->getViolations()->get(0)->getFqcn()
        );
    }

    public function test_classes_with_uuid_should_have_uuid_trait(): void
    {
        $dir = __DIR__.'/Fixtures';

        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('Arkitect\Tests\Integration\PHPUnit\Fixtures\Models'))
            ->should(new HaveTrait('HasUuid'))
            ->because('all models should use UUID');

        $runner->run($dir, $rule);

        self::assertCount(0, $runner->getViolations());
        self::assertCount(0, $runner->getParsingErrors());
    }
}
