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
        $classDescriptionBuilder = ClassDescriptionBuilder::create();
        $classDescriptionBuilder->setClassName($FQCN);

        $classDependency = new ClassDependency('DepClass', 10);

        $classDescriptionBuilder->addDependency($classDependency);
        $classDescriptionBuilder->addInterface('InterfaceClass', 10);

        $classDescription = $classDescriptionBuilder->get();

        $this->assertInstanceOf(ClassDescription::class, $classDescription);

        $this->assertEquals($FQCN, $classDescription->getName());
        $this->assertEquals($FQCN, $classDescription->getFQCN());
    }

    public function test_it_should_create_final_class(): void
    {
        $FQCN = 'HappyIsland';
        $classDescriptionBuilder = ClassDescriptionBuilder::create();
        $classDescriptionBuilder->setClassName($FQCN);
        $classDescriptionBuilder->setFinal(true);

        $classDescription = $classDescriptionBuilder->get();

        $this->assertInstanceOf(ClassDescription::class, $classDescription);

        $this->assertTrue($classDescription->isFinal());
    }

    public function test_it_should_create_not_final_class(): void
    {
        $FQCN = 'HappyIsland';
        $classDescriptionBuilder = ClassDescriptionBuilder::create();
        $classDescriptionBuilder->setClassName($FQCN);
        $classDescriptionBuilder->setFinal(false);

        $classDescription = $classDescriptionBuilder->get();

        $this->assertInstanceOf(ClassDescription::class, $classDescription);

        $this->assertFalse($classDescription->isFinal());
    }

    public function test_it_should_create_abstract_class(): void
    {
        $FQCN = 'HappyIsland';
        $classDescriptionBuilder = ClassDescriptionBuilder::create();
        $classDescriptionBuilder->setClassName($FQCN);
        $classDescriptionBuilder->setAbstract(true);

        $classDescription = $classDescriptionBuilder->get();

        $this->assertInstanceOf(ClassDescription::class, $classDescription);

        $this->assertTrue($classDescription->isAbstract());
    }

    public function test_it_should_create_not_abstract_class(): void
    {
        $FQCN = 'HappyIsland';
        $classDescriptionBuilder = ClassDescriptionBuilder::create();
        $classDescriptionBuilder->setClassName($FQCN);
        $classDescriptionBuilder->setAbstract(false);

        $classDescription = $classDescriptionBuilder->get();

        $this->assertInstanceOf(ClassDescription::class, $classDescription);

        $this->assertFalse($classDescription->isAbstract());
    }

    public function test_it_should_create_annotated_class(): void
    {
        $FQCN = 'HappyIsland';
        $classDescriptionBuilder = ClassDescriptionBuilder::create();
        $classDescriptionBuilder->setClassName($FQCN);
        $classDescriptionBuilder->addDocBlock('/**
 * @psalm-immutable
 */');

        $classDescription = $classDescriptionBuilder->get();

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
        $classDescriptionBuilder = ClassDescriptionBuilder::create();
        $classDescriptionBuilder->setClassName($FQCN);
        $classDescriptionBuilder->addAttribute('AttrClass', 27);

        $classDescription = $classDescriptionBuilder->get();

        self::assertEquals(
            [FullyQualifiedClassName::fromString('AttrClass')],
            $classDescription->getAttributes()
        );
    }

    public function test_it_should_set_file_path(): void
    {
        $filePath = 'filePath/filePath2';
        $classDescriptionBuilder = ClassDescriptionBuilder::create();
        $classDescriptionBuilder->setClassName('FQCN');
        $classDescriptionBuilder->setFilePath($filePath);

        $classDescription = $classDescriptionBuilder->get();
        $this->assertEquals($filePath, $classDescription->fullPath());
    }

    public function test_it_should_create_interface(): void
    {
        $FQCN = 'HappyIsland';
        $classDescriptionBuilder = ClassDescriptionBuilder::create();
        $classDescriptionBuilder->setClassName($FQCN);
        $classDescriptionBuilder->setInterface(true);

        $classDescription = $classDescriptionBuilder->get();

        $this->assertInstanceOf(ClassDescription::class, $classDescription);

        $this->assertTrue($classDescription->isInterface());
    }

    public function test_it_should_create_not_interface(): void
    {
        $FQCN = 'HappyIsland';
        $classDescriptionBuilder = ClassDescriptionBuilder::create();
        $classDescriptionBuilder->setClassName($FQCN);
        $classDescriptionBuilder->setInterface(false);

        $classDescription = $classDescriptionBuilder->get();

        $this->assertInstanceOf(ClassDescription::class, $classDescription);

        $this->assertFalse($classDescription->isInterface());
    }
}
