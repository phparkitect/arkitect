<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\ClassDescriptionBuilder;
use PHPUnit\Framework\TestCase;

class ClassDescriptionBuilderTest extends TestCase
{
    public function test_it_should_create_builder_with_dependency_and_interface(): void
    {
        $FQCN = 'HappyIsland';
        $classDescriptionBuilder = ClassDescriptionBuilder::create($FQCN);

        $classDependency = new ClassDependency('DepClass', 10);

        $classDescriptionBuilder->addDependency($classDependency);
        $classDescriptionBuilder->addInterface('InterfaceClass', 10);

        $classDescription = $classDescriptionBuilder->get();

        $this->assertInstanceOf(ClassDescription::class, $classDescription);

        $this->assertEquals($FQCN, $classDescription->getName());
        $this->assertEquals($FQCN, $classDescription->getFQCN());
    }
}
