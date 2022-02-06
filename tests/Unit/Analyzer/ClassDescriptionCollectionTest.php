<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\ClassDescriptionCollection;
use Arkitect\Analyzer\FullyQualifiedClassName;
use PHPUnit\Framework\TestCase;

class ClassDescriptionCollectionTest extends TestCase
{
    public function test_it_should_create_collection(): void
    {
        $collection = new ClassDescriptionCollection();
        $this->assertEquals([], $collection->getClassDescriptions());
    }

    public function test_it_should_add_elements_to_collection(): void
    {
        $collection = new ClassDescriptionCollection();

        $classDescription = ClassDescription::build('Fruit\Banana')->get();
        $collection->add($classDescription);
        $this->assertEquals([
            $classDescription->getFQCN() => $classDescription,
        ], $collection->getClassDescriptions());
    }

    public function test_it_should_get_dependencies(): void
    {
        $collection = new ClassDescriptionCollection();

        $builder = ClassDescription::build('Fruit\Banana');
        $builder->addDependency(new ClassDependency('Foo\Bar', 10));
        $classDescription1 = $builder->get();

        $builder = ClassDescription::build('Foo\Bar');
        $classDescription2 = $builder->get();

        $collection->add($classDescription1);
        $collection->add($classDescription2);
        $this->assertEquals(
            ['Foo\Bar' => new ClassDependency('Foo\Bar', 10)],
            $collection->getDependencies($classDescription1->getFQCN())
        );
    }

    public function test_it_should_get_return_empty_if_class_not_found(): void
    {
        $collection = new ClassDescriptionCollection();

        $builder = ClassDescription::build('Fruit\Banana');
        $builder->addDependency(new ClassDependency('Foo\Bar', 10));
        $classDescription = $builder->get();

        $builder = ClassDescription::build('Fruit\Ananas');
        $builder->addDependency(new ClassDependency('Foo\Bar', 10));
        $anotherClassDescription = $builder->get();

        $collection->add($classDescription);
        $this->assertEquals(
            [],
            $collection->getDependencies($anotherClassDescription->getFQCN())
        );
    }

    public function test_it_should_get_dependencies_recursively(): void
    {
        $collection = new ClassDescriptionCollection();

        $builder = ClassDescription::build('Fruit\Banana');
        $builder->addDependency(new ClassDependency('Foo\Bar', 10));
        $classDescription1 = $builder->get();

        $builder = ClassDescription::build('Foo\Bar');
        $builder->addDependency(new ClassDependency('Foo\Baz', 11));
        $classDescription2 = $builder->get();

        $builder = ClassDescription::build('Foo\Baz');
        $builder->addDependency(new ClassDependency('Foo\Cat', 12));
        $classDescription3 = $builder->get();

        $builder = ClassDescription::build('Foo\Cat');
        $classDescription4 = $builder->get();

        $collection->add($classDescription1);
        $collection->add($classDescription2);
        $collection->add($classDescription3);
        $collection->add($classDescription4);

        $this->assertEquals([
            'Foo\Bar' => new ClassDependency('Foo\Bar', 10),
            'Foo\Baz' => new ClassDependency('Foo\Baz', 11),
            'Foo\Cat' => new ClassDependency('Foo\Cat', 12),
        ], $collection->getDependencies($classDescription1->getFQCN()));

        $this->assertEquals([
            'Foo\Baz' => new ClassDependency('Foo\Baz', 11),
            'Foo\Cat' => new ClassDependency('Foo\Cat', 12),
        ], $collection->getDependencies($classDescription2->getFQCN()));
    }

    public function test_it_should_get_extends_recursively(): void
    {
        $collection = new ClassDescriptionCollection();

        $builder = ClassDescription::build('Fruit\Banana');
        $builder->setExtends(FullyQualifiedClassName::fromString('Foo\Bar')->toString(), 1);
        $classDescription1 = $builder->get();

        $builder = ClassDescription::build('Foo\Bar');
        $builder->setExtends(FullyQualifiedClassName::fromString('Foo\Baz')->toString(), 1);
        $classDescription2 = $builder->get();

        $builder = ClassDescription::build('Foo\Baz');
        $builder->setExtends(FullyQualifiedClassName::fromString('Foo\Cat')->toString(), 1);
        $classDescription3 = $builder->get();

        $builder = ClassDescription::build('Foo\Cat');
        $classDescription4 = $builder->get();

        $collection->add($classDescription1);
        $collection->add($classDescription2);
        $collection->add($classDescription3);
        $collection->add($classDescription4);

        $this->assertEquals([
            'Foo\Bar' => FullyQualifiedClassName::fromString('Foo\Bar'),
            'Foo\Baz' => FullyQualifiedClassName::fromString('Foo\Baz'),
            'Foo\Cat' => FullyQualifiedClassName::fromString('Foo\Cat'),
        ], $collection->getExtends($classDescription1->getFQCN()));

        $this->assertEquals([
            'Foo\Baz' => FullyQualifiedClassName::fromString('Foo\Baz'),
            'Foo\Cat' => FullyQualifiedClassName::fromString('Foo\Cat'),
        ], $collection->getExtends($classDescription2->getFQCN()));
    }

    public function test_it_should_get_implements_recursively(): void
    {
        $collection = new ClassDescriptionCollection();

        $builder = ClassDescription::build('Fruit\Banana');
        $builder->addInterface('Foo\Bar', 10);
        $classDescription1 = $builder->get();

        $builder = ClassDescription::build('Foo\Bar');
        $builder->addInterface('Foo\Baz', 10);
        $classDescription2 = $builder->get();

        $builder = ClassDescription::build('Foo\Baz');
        $builder->addInterface('Foo\Cat', 10);
        $classDescription3 = $builder->get();

        $builder = ClassDescription::build('Foo\Cat');
        $classDescription4 = $builder->get();

        $collection->add($classDescription1);
        $collection->add($classDescription2);
        $collection->add($classDescription3);
        $collection->add($classDescription4);

        $this->assertEquals([
            'Foo\Bar' => FullyQualifiedClassName::fromString('Foo\Bar'),
            'Foo\Baz' => FullyQualifiedClassName::fromString('Foo\Baz'),
            'Foo\Cat' => FullyQualifiedClassName::fromString('Foo\Cat'),
        ], $collection->getInterfaces($classDescription1->getFQCN()));

        $this->assertEquals([
            'Foo\Baz' => FullyQualifiedClassName::fromString('Foo\Baz'),
            'Foo\Cat' => FullyQualifiedClassName::fromString('Foo\Cat'),
        ], $collection->getInterfaces($classDescription2->getFQCN()));
    }

    public function test_it_exists_already(): void
    {
        $collection = new ClassDescriptionCollection();
        $builder = ClassDescription::build('Fruit\Banana');
        $builder->addDependency(new ClassDependency('Foo\Bar', 10));
        $classDescription1 = $builder->get();

        $collection->add($classDescription1);

        $this->assertTrue($collection->exists($classDescription1->getFQCN()));
    }

    public function test_it_not_exists_already(): void
    {
        $collection = new ClassDescriptionCollection();
        $builder = ClassDescription::build('Fruit\Banana');
        $builder->addDependency(new ClassDependency('Foo\Bar', 10));
        $classDescription1 = $builder->get();

        $collection->add($classDescription1);

        $this->assertFalse($collection->exists('Another\One'));
    }

    public function test_get_from_collection(): void
    {
        $collection = new ClassDescriptionCollection();
        $builder = ClassDescription::build('Fruit\Banana');
        $builder->addDependency(new ClassDependency('Foo\Bar', 10));
        $classDescription1 = $builder->get();

        $collection->add($classDescription1);

        $this->assertEquals($classDescription1, $collection->get('Fruit\Banana'));
    }
}
