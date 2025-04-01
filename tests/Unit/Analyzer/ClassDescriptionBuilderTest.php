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
    public function test_it_should_create_builder_with_dependency_and_interface(): void
    {
        $FQCN = 'HappyIsland';
        $classDescriptionBuilder = new ClassDescriptionBuilder();
        $classDescriptionBuilder
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->addDependency(new ClassDependency('DepClass', 10))
            ->addInterface('InterfaceClass', 10);

        $classDescription = $classDescriptionBuilder->build();

        $this->assertInstanceOf(ClassDescription::class, $classDescription);

        $this->assertEquals($FQCN, $classDescription->getName());
        $this->assertEquals($FQCN, $classDescription->getFQCN());
    }

    public function test_it_should_create_final_class(): void
    {
        $FQCN = 'HappyIsland';
        $classDescriptionBuilder = new ClassDescriptionBuilder();
        $classDescriptionBuilder
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->setFinal(true);

        $classDescription = $classDescriptionBuilder->build();

        $this->assertInstanceOf(ClassDescription::class, $classDescription);

        $this->assertTrue($classDescription->isFinal());
    }

    public function test_it_should_create_not_final_class(): void
    {
        $FQCN = 'HappyIsland';
        $classDescriptionBuilder = new ClassDescriptionBuilder();
        $classDescriptionBuilder
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->setFinal(false);

        $classDescription = $classDescriptionBuilder->build();

        $this->assertInstanceOf(ClassDescription::class, $classDescription);

        $this->assertFalse($classDescription->isFinal());
    }

    public function test_it_should_create_abstract_class(): void
    {
        $FQCN = 'HappyIsland';
        $classDescriptionBuilder = new ClassDescriptionBuilder();
        $classDescriptionBuilder
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->setAbstract(true);

        $classDescription = $classDescriptionBuilder->build();

        $this->assertInstanceOf(ClassDescription::class, $classDescription);

        $this->assertTrue($classDescription->isAbstract());
    }

    public function test_it_should_create_not_abstract_class(): void
    {
        $FQCN = 'HappyIsland';
        $classDescriptionBuilder = new ClassDescriptionBuilder();
        $classDescriptionBuilder
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->setAbstract(false);

        $classDescription = $classDescriptionBuilder->build();

        $this->assertInstanceOf(ClassDescription::class, $classDescription);

        $this->assertFalse($classDescription->isAbstract());
    }

    public function test_it_should_create_annotated_class(): void
    {
        $FQCN = 'HappyIsland';
        $classDescriptionBuilder = new ClassDescriptionBuilder();
        $classDescriptionBuilder
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->addDocBlock('/**
 * @psalm-immutable
 */');

        $classDescription = $classDescriptionBuilder->build();

        $this->assertInstanceOf(ClassDescription::class, $classDescription);

        $this->assertEquals(
            ['/**
 * @psalm-immutable
 */'],
            $classDescription->getDocBlock()
        );
    }

    public function test_it_should_add_attributes(): void
    {
        $FQCN = 'HappyIsland';
        $classDescriptionBuilder = new ClassDescriptionBuilder();
        $classDescriptionBuilder
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->addAttribute('AttrClass', 27);

        $classDescription = $classDescriptionBuilder->build();

        self::assertEquals(
            [FullyQualifiedClassName::fromString('AttrClass')],
            $classDescription->getAttributes()
        );
    }

    public function test_it_should_create_interface(): void
    {
        $FQCN = 'HappyIsland';
        $classDescriptionBuilder = new ClassDescriptionBuilder();
        $classDescriptionBuilder
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->setInterface(true);

        $classDescription = $classDescriptionBuilder->build();

        $this->assertInstanceOf(ClassDescription::class, $classDescription);

        $this->assertTrue($classDescription->isInterface());
    }

    public function test_it_should_create_not_interface(): void
    {
        $FQCN = 'HappyIsland';
        $classDescriptionBuilder = new ClassDescriptionBuilder();
        $classDescriptionBuilder
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->setInterface(false);

        $classDescription = $classDescriptionBuilder->build();

        $this->assertInstanceOf(ClassDescription::class, $classDescription);

        $this->assertFalse($classDescription->isInterface());
    }

    public function test_it_should_create_trait(): void
    {
        $FQCN = 'HappyIsland';
        $classDescriptionBuilder = new ClassDescriptionBuilder();
        $classDescriptionBuilder
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->setTrait(true);

        $classDescription = $classDescriptionBuilder->build();

        $this->assertInstanceOf(ClassDescription::class, $classDescription);

        $this->assertTrue($classDescription->isTrait());
    }

    public function test_it_should_create_not_trait(): void
    {
        $FQCN = 'HappyIsland';
        $classDescriptionBuilder = new ClassDescriptionBuilder();
        $classDescriptionBuilder->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->setTrait(false);

        $classDescription = $classDescriptionBuilder->build();

        $this->assertInstanceOf(ClassDescription::class, $classDescription);

        $this->assertFalse($classDescription->isTrait());
    }
}
