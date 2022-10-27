<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\ClassDescriptionBuilder;
use PHPUnit\Framework\TestCase;

class ClassDescriptionTest extends TestCase
{
    /** @var ClassDescriptionBuilder */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = ClassDescription::build('Fruit\Banana');
    }

    public function test_should_return_true_if_name_matches(): void
    {
        $cd = $this->builder->get();

        $this->assertTrue($cd->nameMatches('Banana'));
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

    public function test_should_return_true_if_there_class_is_in_namespace_array(): void
    {
        $cd = $this->builder->get();

        $this->assertTrue($cd->namespaceMatchesOneOfTheseNamespaces(['Fruit']));
    }

    public function test_should_return_true_if_is_annotated_with(): void
    {
        $cd = $this->builder
            ->addDocBlock('/**
 * @psalm-immutable
 */')
            ->get();

        $this->assertTrue($cd->containsDocBlock('@psalm-immutable'));
    }

    public function test_should_return_false_if_not_annotated_with(): void
    {
        $cd = $this->builder
            ->addDocBlock('/**
 * @psalm-immutable
 */')
            ->get();

        $this->assertFalse($cd->containsDocBlock('@another-annotation'));
    }

    public function test_should_return_true_if_has_attribute(): void
    {
        $cd = $this->builder
            ->addAttribute('FooAttr', 27)
            ->get();

        self::assertTrue($cd->hasAttribute('FooAttr'));
        self::assertTrue($cd->hasAttribute('Foo*'));
    }

    public function test_should_return_false_if_not_has_attribute(): void
    {
        $cd = $this->builder
            ->addAttribute('FooAttr', 27)
            ->get();

        self::assertFalse($cd->hasAttribute('Bar'));
    }
}
