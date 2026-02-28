<?php

declare(strict_types=1);

namespace Arkitect\Tests\Integration;

use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\IsNotReadonly;
use Arkitect\Expression\ForClasses\IsReadonly;
use Arkitect\Rules\Rule;
use Arkitect\Tests\Utils\TestRunner;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class IsReadonlyTest extends TestCase
{
    public function test_is_readonly_in_that_should_not_consider_traits_enums_interfaces(): void
    {
        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new IsReadonly())
            ->should(new HaveNameMatching('*Readonly'))
            ->because('we want to prefix readonly classes');

        $runner->run($this->createClasses(), $rule);

        self::assertCount(0, $runner->getViolations());
    }

    public function test_is_readonly_in_should_should_consider_traits_enums_interfaces(): void
    {
        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new HaveNameMatching('My*'))
            ->should(new IsReadonly())
            ->because('everything in the app namespace should be readonly');

        $runner->run($this->createClasses(), $rule);

        self::assertCount(3, $runner->getViolations());

        self::assertEquals('App\MyEnum', $runner->getViolations()->get(0)->getFqcn());
        self::assertEquals('App\MyInterface', $runner->getViolations()->get(1)->getFqcn());
        self::assertEquals('App\MyTrait', $runner->getViolations()->get(2)->getFqcn());
    }

    public function test_is_not_readonly_in_should_should_consider_traits_enums_interfaces(): void
    {
        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new HaveNameMatching('My*'))
            ->should(new IsNotReadonly())
            ->because('everything in the app namespace should not be final');

        $runner->run($this->createClasses(), $rule);

        self::assertCount(1, $runner->getViolations());

        self::assertEquals('App\MyReadonly', $runner->getViolations()->get(0)->getFqcn());
    }

    protected function createClasses(): string
    {
        $structure = [
            'App' => [
                'MyInterface.php' => '<?php namespace App { interface MyInterface {} };',
                'MyEnum.php' => '<?php namespace App { enum MyEnum {} };',
                'MyTrait.php' => '<?php namespace App { trait MyTrait {} };',
                'MyReadonly.php' => '<?php namespace App { readonly class MyReadonly {} };',
            ],
        ];

        return vfsStream::setup('root', null, $structure)->url();
    }
}
