<?php

declare(strict_types=1);

namespace ArkitectTests\Analyzer;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use PHPUnit\Framework\TestCase;

class ClassDescriptionTest extends TestCase
{
    /**
     * @var \Arkitect\Analyzer\ClassDescriptionBuilder
     */
    private $builder;

    public function setUp(): void
    {
        $this->builder = ClassDescription::build('Fruit\Banana', 'my/path');
    }

    public function test_should_return_true_if_name_matches(): void
    {
        $cd = $this->builder->get();

        $this->assertTrue($cd->nameMatches('Banana'));
    }

    public function test_should_return_true_if_implements_interface(): void
    {
        $cd = $this->builder
            ->addInterface('Fruit\EdibleInterface', 12)
            ->get();

        $this->assertTrue($cd->implements('Fruit\EdibleInterface'));
        $this->assertFalse($cd->implements('Fruit\AnotherInterface'));
    }

    public function test_should_return_true_if_there_is_a_dependency(): void
    {
        $cd = $this->builder
            ->addDependency(new ClassDependency('Fruit\Mango', 12))
            ->addDependency(new ClassDependency('Vegetablus\Radish', 12))
            ->get();

        $this->assertTrue($cd->dependsOn('Fruit\Mango'));
        $this->assertTrue($cd->dependsOnClass('F*\Mango'));
        $this->assertTrue($cd->dependsOnNamespace('Vegetabl*'));
        $this->assertTrue($cd->dependsOnly('Vegetabl*'));
    }

    public function test_should_return_true_if_there_class_is_in_namespace(): void
    {
        $cd = $this->builder->get();

        $this->assertTrue($cd->isInNamespace('Fruit'));
    }

    public function test_should_return_name(): void
    {
        $cd = $this->builder->get();


        $this->assertEquals('Banana', $cd->getName());
    }
}
