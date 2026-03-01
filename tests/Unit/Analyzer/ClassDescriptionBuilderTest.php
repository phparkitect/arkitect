<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Analyzer\FullyQualifiedClassName;
use PHPUnit\Framework\TestCase;

class ClassDescriptionBuilderTest extends TestCase
{
    public function test_it_should_create_builder_with_dependency(): void
    {
        $FQCN = 'HappyIsland';

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->addDependency(new ClassDependency('DepClass', 10))
            ->build();

        self::assertInstanceOf(ClassDescription::class, $classDescription);

        self::assertEquals($FQCN, $classDescription->getName());
        self::assertEquals($FQCN, $classDescription->getFQCN());
    }

    public function test_it_should_create_final_class(): void
    {
        $FQCN = 'HappyIsland';

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->setFinal(true)
            ->build();

        self::assertInstanceOf(ClassDescription::class, $classDescription);

        self::assertTrue($classDescription->isFinal());
    }

    public function test_it_should_create_not_final_class(): void
    {
        $FQCN = 'HappyIsland';

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->setFinal(false)
            ->build();

        self::assertInstanceOf(ClassDescription::class, $classDescription);

        self::assertFalse($classDescription->isFinal());
    }

    public function test_it_should_create_abstract_class(): void
    {
        $FQCN = 'HappyIsland';

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->setAbstract(true)
            ->build();

        self::assertInstanceOf(ClassDescription::class, $classDescription);

        self::assertTrue($classDescription->isAbstract());
    }

    public function test_it_should_create_not_abstract_class(): void
    {
        $FQCN = 'HappyIsland';

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->setAbstract(false)
            ->build();

        self::assertInstanceOf(ClassDescription::class, $classDescription);

        self::assertFalse($classDescription->isAbstract());
    }

    public function test_it_should_create_annotated_class(): void
    {
        $FQCN = 'HappyIsland';

        $docBlock = <<< 'EOT'
        /**
         * @psalm-immutable
         */
        EOT;

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->addDocBlock($docBlock)
            ->build();

        self::assertInstanceOf(ClassDescription::class, $classDescription);
        self::assertEquals([$docBlock], $classDescription->getDocBlock());
    }

    public function test_it_should_add_attributes(): void
    {
        $FQCN = 'HappyIsland';

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->addAttribute('AttrClass', 27)
            ->build();

        self::assertEquals(
            [FullyQualifiedClassName::fromString('AttrClass')],
            $classDescription->getAttributes()
        );
    }

    public function test_it_should_add_traits(): void
    {
        $FQCN = 'HappyIsland';

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->addTrait('TraitClass', 15)
            ->build();

        self::assertEquals(
            [FullyQualifiedClassName::fromString('TraitClass')],
            $classDescription->getTraits()
        );
    }

    public function test_it_should_create_interface(): void
    {
        $FQCN = 'HappyIsland';

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->setInterface(true)
            ->build();

        self::assertInstanceOf(ClassDescription::class, $classDescription);
        self::assertTrue($classDescription->isInterface());
    }

    public function test_it_should_create_not_interface(): void
    {
        $FQCN = 'HappyIsland';

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->setInterface(false)
            ->build();

        self::assertInstanceOf(ClassDescription::class, $classDescription);
        self::assertFalse($classDescription->isInterface());
    }

    public function test_it_should_create_trait(): void
    {
        $FQCN = 'HappyIsland';

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->setTrait(true)
            ->build();

        self::assertInstanceOf(ClassDescription::class, $classDescription);
        self::assertTrue($classDescription->isTrait());
    }

    public function test_it_should_create_not_trait(): void
    {
        $FQCN = 'HappyIsland';

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->setTrait(false)
            ->build();

        self::assertInstanceOf(ClassDescription::class, $classDescription);
        self::assertFalse($classDescription->isTrait());
    }

    public function test_it_should_filter_out_php_core_classes(): void
    {
        $FQCN = 'MyClass';

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->addDependency(new ClassDependency('DateTime', 10))
            ->addDependency(new ClassDependency('Exception', 15))
            ->addDependency(new ClassDependency('PDO', 20))
            ->build();

        self::assertInstanceOf(ClassDescription::class, $classDescription);
        self::assertCount(0, $classDescription->getDependencies());
    }

    public function test_it_should_not_filter_user_defined_classes_in_root_namespace(): void
    {
        $FQCN = 'MyClass';

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->addDependency(new ClassDependency('NonExistentUserClass', 10))
            ->build();

        self::assertInstanceOf(ClassDescription::class, $classDescription);
        self::assertCount(1, $classDescription->getDependencies());
        self::assertEquals('NonExistentUserClass', $classDescription->getDependencies()[0]->getFQCN()->toString());
    }

    public function test_it_should_not_filter_user_defined_classes_with_namespace(): void
    {
        $FQCN = 'MyClass';

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->addDependency(new ClassDependency('Vendor\Package\SomeClass', 10))
            ->addDependency(new ClassDependency('App\Domain\Entity', 15))
            ->build();

        self::assertInstanceOf(ClassDescription::class, $classDescription);
        self::assertCount(2, $classDescription->getDependencies());
    }

    public function test_it_should_filter_mixed_dependencies_correctly(): void
    {
        $FQCN = 'MyClass';

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->addDependency(new ClassDependency('DateTime', 10))
            ->addDependency(new ClassDependency('Vendor\Package\SomeClass', 15))
            ->addDependency(new ClassDependency('Exception', 20))
            ->addDependency(new ClassDependency('NonExistentUserClass', 25))
            ->addDependency(new ClassDependency('PDO', 30))
            ->build();

        self::assertInstanceOf(ClassDescription::class, $classDescription);
        self::assertCount(2, $classDescription->getDependencies());

        $dependencies = $classDescription->getDependencies();
        self::assertEquals('Vendor\Package\SomeClass', $dependencies[0]->getFQCN()->toString());
        self::assertEquals('NonExistentUserClass', $dependencies[1]->getFQCN()->toString());
    }

    public function test_it_should_filter_internal_classes_with_namespaces(): void
    {
        $FQCN = 'MyClass';

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->addDependency(new ClassDependency('ReflectionClass', 10))
            ->addDependency(new ClassDependency('App\MyClass', 15))
            ->build();

        self::assertInstanceOf(ClassDescription::class, $classDescription);
        self::assertCount(1, $classDescription->getDependencies());
        self::assertEquals('App\MyClass', $classDescription->getDependencies()[0]->getFQCN()->toString());
    }
}
