<?php

declare(strict_types=1);

namespace Arkitect\Tests\Integration;

use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\IsAbstract;
use Arkitect\Expression\ForClasses\IsNotAbstract;
use Arkitect\Expression\ForClasses\IsNotEnum;
use Arkitect\Expression\ForClasses\IsNotFinal;
use Arkitect\Expression\ForClasses\IsNotInterface;
use Arkitect\Expression\ForClasses\IsNotReadonly;
use Arkitect\Expression\ForClasses\IsNotTrait;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;
use Arkitect\Tests\Utils\TestRunner;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class IsAbstractTest extends TestCase
{
    public function test_is_abstract_in_that_should_not_consider_final_traits_enums_interfaces(): void
    {
        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new IsAbstract())
            ->should(new HaveNameMatching('*Abstract'))
            ->because('we want to prefix abstract classes');

        $runner->run($this->createClasses(), $rule);

        self::assertCount(0, $runner->getViolations());
        self::assertCount(0, $runner->getParsingErrors());
    }

    public function test_is_abstract_in_should_should_consider_final_traits_enums_interfaces(): void
    {
        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new HaveNameMatching('My*'))
            ->should(new IsAbstract())
            ->because('everything in the app namespace should be abstract');

        $runner->run($this->createClasses(), $rule);

        self::assertCount(4, $runner->getViolations());
        self::assertCount(0, $runner->getParsingErrors());

        self::assertEquals('App\MyEnum', $runner->getViolations()->get(0)->getFqcn());
        self::assertEquals('App\MyFinal', $runner->getViolations()->get(1)->getFqcn());
        self::assertEquals('App\MyInterface', $runner->getViolations()->get(2)->getFqcn());
        self::assertEquals('App\MyTrait', $runner->getViolations()->get(3)->getFqcn());
    }

    public function test_is_not_abstract_in_should_should_consider_final_traits_enums_interfaces(): void
    {
        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new HaveNameMatching('My*'))
            ->should(new IsNotAbstract())
            ->because('everything in the app namespace should be abstract');

        $runner->run($this->createClasses(), $rule);

        self::assertCount(1, $runner->getViolations());
        self::assertCount(0, $runner->getParsingErrors());

        self::assertEquals('App\MyAbstract', $runner->getViolations()->get(0)->getFqcn());
    }

    public function test_it_can_check_multiple_class_properties(): void
    {
        $structure = [
            'App' => [
                'BadCode' => [
                    'BadCode.php' => '<?php
                        declare(strict_types=1);

                        namespace App\BadCode;

                        use App\HappyIsland\HappyClass;

                        class BadCode
                        {
                            private $happy;

                            public function __construct(HappyClass $happy)
                            {
                                $this->happy = $happy;
                            }
                        }
                        ',
                ],
                'HappyIsland' => [
                    'HappyClass.php' => '<?php
                        declare(strict_types=1);

                        namespace App\HappyIsland;

                        use App\BadCode\BadCode;

                        class HappyClass
                        {
                            /**
                             * @var BadCode
                             */
                            private $bad;

                            public function __construct(BadCode $bad)
                            {
                                $this->bad = $bad;
                            }
                        }',
                ],
                'OtherBadCode' => [
                    'OtherBadCode.php' => '<?php
                        declare(strict_types=1);

                        namespace App\OtherBadCode;

                        use App\HappyIsland\HappyClass;

                        class OtherBadCode
                        {
                            private $happy;

                            public function __construct(HappyClass $happy)
                            {
                                $this->happy = $happy;
                            }
                        }',
                ],
            ],
        ];

        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\BadCode'))
            ->andThat(new ResideInOneOfTheseNamespaces('App\HappyIsland'))
            ->should(new IsNotFinal())
            ->andShould(new IsNotReadonly())
            ->andShould(new IsNotAbstract())
            ->andShould(new IsNotEnum())
            ->andShould(new IsNotInterface())
            ->andShould(new IsNotTrait())
            ->because('some reason');

        $runner->run(vfsStream::setup('root', null, $structure)->url(), $rule);

        self::assertCount(0, $runner->getViolations());
        self::assertCount(0, $runner->getParsingErrors());
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
