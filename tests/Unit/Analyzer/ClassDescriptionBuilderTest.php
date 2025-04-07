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
}
