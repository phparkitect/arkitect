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

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->addDependency(new ClassDependency('DepClass', 10))
            ->addInterface('InterfaceClass', 10)
            ->build();

        $this->assertInstanceOf(ClassDescription::class, $classDescription);

        $this->assertEquals($FQCN, $classDescription->getName());
        $this->assertEquals($FQCN, $classDescription->getFQCN());
    }

    public function test_it_should_create_final_class(): void
    {
        $FQCN = 'HappyIsland';

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->setFinal(true)
            ->build();

        $this->assertInstanceOf(ClassDescription::class, $classDescription);

        $this->assertTrue($classDescription->isFinal());
    }

    public function test_it_should_create_not_final_class(): void
    {
        $FQCN = 'HappyIsland';

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->setFinal(false)
            ->build();

        $this->assertInstanceOf(ClassDescription::class, $classDescription);

        $this->assertFalse($classDescription->isFinal());
    }

    public function test_it_should_create_abstract_class(): void
    {
        $FQCN = 'HappyIsland';

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->setAbstract(true)
            ->build();

        $this->assertInstanceOf(ClassDescription::class, $classDescription);

        $this->assertTrue($classDescription->isAbstract());
    }

    public function test_it_should_create_not_abstract_class(): void
    {
        $FQCN = 'HappyIsland';

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->setAbstract(false)
            ->build();

        $this->assertInstanceOf(ClassDescription::class, $classDescription);

        $this->assertFalse($classDescription->isAbstract());
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

        $this->assertInstanceOf(ClassDescription::class, $classDescription);
        $this->assertEquals([$docBlock], $classDescription->getDocBlock());
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

    public function test_it_should_create_interface(): void
    {
        $FQCN = 'HappyIsland';

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->setInterface(true)
            ->build();

        $this->assertInstanceOf(ClassDescription::class, $classDescription);
        $this->assertTrue($classDescription->isInterface());
    }

    public function test_it_should_create_not_interface(): void
    {
        $FQCN = 'HappyIsland';

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->setInterface(false)
            ->build();

        $this->assertInstanceOf(ClassDescription::class, $classDescription);
        $this->assertFalse($classDescription->isInterface());
    }

    public function test_it_should_create_trait(): void
    {
        $FQCN = 'HappyIsland';

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->setTrait(true)
            ->build();

        $this->assertInstanceOf(ClassDescription::class, $classDescription);
        $this->assertTrue($classDescription->isTrait());
    }

    public function test_it_should_create_not_trait(): void
    {
        $FQCN = 'HappyIsland';

        $classDescription = (new ClassDescriptionBuilder())
            ->setFilePath('src/Foo.php')
            ->setClassName($FQCN)
            ->setTrait(false)
            ->build();

        $this->assertInstanceOf(ClassDescription::class, $classDescription);
        $this->assertFalse($classDescription->isTrait());
    }
}
