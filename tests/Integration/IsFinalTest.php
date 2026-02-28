<?php

declare(strict_types=1);

namespace Arkitect\Tests\Integration;

use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\IsFinal;
use Arkitect\Expression\ForClasses\IsNotFinal;
use Arkitect\Rules\Rule;
use Arkitect\Tests\Utils\TestRunner;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class IsFinalTest extends TestCase
{
    public function test_is_final_in_that_should_not_consider_abstract_traits_enums_interfaces(): void
    {
        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new IsFinal())
            ->should(new HaveNameMatching('*Final'))
            ->because('we want to prefix final classes');

        $runner->run($this->createClasses(), $rule);

        self::assertCount(0, $runner->getViolations());
    }

    public function test_is_final_in_should_should_consider_final_traits_enums_interfaces(): void
    {
        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new HaveNameMatching('My*'))
            ->should(new IsFinal())
            ->because('everything in the app namespace should be final');

        $runner->run($this->createClasses(), $rule);

        self::assertCount(4, $runner->getViolations());

        self::assertEquals('App\MyAbstract', $runner->getViolations()->get(0)->getFqcn());
        self::assertEquals('App\MyEnum', $runner->getViolations()->get(1)->getFqcn());
        self::assertEquals('App\MyInterface', $runner->getViolations()->get(2)->getFqcn());
        self::assertEquals('App\MyTrait', $runner->getViolations()->get(3)->getFqcn());
    }

    public function test_is_not_final_in_should_should_consider_final_traits_enums_interfaces(): void
    {
        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new HaveNameMatching('My*'))
            ->should(new IsNotFinal())
            ->because('everything in the app namespace should not be final');

        $runner->run($this->createClasses(), $rule);

        self::assertCount(1, $runner->getViolations());

        self::assertEquals('App\MyFinal', $runner->getViolations()->get(0)->getFqcn());
    }

    protected function createClasses(): string
    {
        $structure = [
            'App' => [
                'MyAbstract.php' => '<?php namespace App { abstract class MyAbstract {} };',
                'MyFinal.php' => '<?php namespace App { final class MyFinal {} };',
                'MyInterface.php' => '<?php namespace App { interface MyInterface {} };',
                'MyEnum.php' => '<?php namespace App { enum MyEnum {} };',
                'MyTrait.php' => '<?php namespace App { trait MyTrait {} };',
            ],
        ];

        return vfsStream::setup('root', null, $structure)->url();
    }
}
