<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\ClassDescriptionBuilder;
use PHPUnit\Framework\TestCase;

class ClassDescriptionTest extends TestCase
{
    private ClassDescriptionBuilder $builder;

    public function setUp(): void
    {
        $this->builder = ClassDescription::build('Fruit\Banana');
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
        $this->assertFalse($cd->dependsOnlyOnClassesMatching('Vegetabl*'));
    }

    public function test_should_return_true_if_there_class_is_in_namespace(): void
    {
        $cd = $this->builder->get();

        $this->assertTrue($cd->namespaceMatches('Fruit'));
    }

    public function test_should_return_name(): void
    {
        $cd = $this->builder->get();

        $this->assertEquals('Banana', $cd->getName());
    }
}
