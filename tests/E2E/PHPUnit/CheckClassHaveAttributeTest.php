<?php

declare(strict_types=1);

namespace Arkitect\Tests\E2E\PHPUnit;

use Arkitect\Expression\ForClasses\HaveAttribute;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;
use Arkitect\Tests\Utils\TestRunner;
use PHPUnit\Framework\TestCase;

final class CheckClassHaveAttributeTest extends TestCase
{
    public function test_entities_should_reside_in_app_model(): void
    {
        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new HaveAttribute('Entity'))
            ->should(new ResideInOneOfTheseNamespaces('App\Model'))
            ->because('we use an ORM');

        $runner->run(__DIR__.'/../_fixtures/mvc', $rule);

        $this->assertCount(0, $runner->getViolations());
        $this->assertCount(0, $runner->getParsingErrors());
    }

    public function test_controllers_should_have_name_ending_in_controller(): void
    {
        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new HaveAttribute('AsController'))
            ->should(new HaveNameMatching('*Controller'))
            ->because('its a symfony thing');

        $runner->run(__DIR__.'/../_fixtures/mvc', $rule);

        $this->assertCount(1, $runner->getViolations());
        $this->assertCount(0, $runner->getParsingErrors());

        $this->assertEquals('App\Controller\Foo', $runner->getViolations()->get(0)->getFqcn());
    }

    public function test_controllers_should_have_controller_attribute(): void
    {
        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new HaveNameMatching('*Controller'))
            ->should(new HaveAttribute('AsController'))
            ->because('it configures the service container');

        $runner->run(__DIR__.'/../_fixtures/mvc', $rule);

        $this->assertCount(0, $runner->getViolations());
        $this->assertCount(0, $runner->getParsingErrors());
    }
}
