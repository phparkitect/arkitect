<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer\FileParser;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FileParserFactory;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\CLI\TargetPhpVersion;
use PHPUnit\Framework\TestCase;

class CanResolveInheritedInterfacesTest extends TestCase
{
    public function test_class_extending_parent_should_inherit_parent_interfaces(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace App;

        class MyArrayObject extends \ArrayObject
        {
        }
        EOF;

        $cd = $this->parseCode($code);

        self::assertCount(1, $cd);

        $interfaces = array_map(
            static fn (FullyQualifiedClassName $fqcn): string => $fqcn->toString(),
            $cd[0]->getInterfaces()
        );

        // ArrayObject implements IteratorAggregate, Traversable, ArrayAccess, Serializable, Countable
        self::assertContains('IteratorAggregate', $interfaces);
        self::assertContains('Traversable', $interfaces);
        self::assertContains('ArrayAccess', $interfaces);
        self::assertContains('Countable', $interfaces);
    }

    public function test_class_implementing_interface_should_inherit_parent_interfaces(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace App;

        class MyIterator implements \IteratorAggregate
        {
            public function getIterator(): \Traversable
            {
                return new \ArrayIterator([]);
            }
        }
        EOF;

        $cd = $this->parseCode($code);

        self::assertCount(1, $cd);

        $interfaces = array_map(
            static fn (FullyQualifiedClassName $fqcn): string => $fqcn->toString(),
            $cd[0]->getInterfaces()
        );

        // IteratorAggregate extends Traversable
        self::assertContains('IteratorAggregate', $interfaces);
        self::assertContains('Traversable', $interfaces);
    }

    public function test_class_with_non_autoloadable_parent_should_still_parse(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace App;

        class MyClass extends NonExistent\ParentClass
        {
        }
        EOF;

        $cd = $this->parseCode($code);

        self::assertCount(1, $cd);

        // Should still have the direct extends, just no inherited interfaces
        $extends = array_map(
            static fn (FullyQualifiedClassName $fqcn): string => $fqcn->toString(),
            $cd[0]->getExtends()
        );

        self::assertContains('App\NonExistent\ParentClass', $extends);
        self::assertCount(0, $cd[0]->getInterfaces());
    }

    public function test_inherited_interfaces_should_not_duplicate_direct_interfaces(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace App;

        class MyClass extends \ArrayObject implements \Countable
        {
        }
        EOF;

        $cd = $this->parseCode($code);

        self::assertCount(1, $cd);

        $interfaces = array_map(
            static fn (FullyQualifiedClassName $fqcn): string => $fqcn->toString(),
            $cd[0]->getInterfaces()
        );

        // Countable should appear only once even though it's both direct and inherited
        $countableOccurrences = array_count_values($interfaces)['Countable'] ?? 0;
        self::assertEquals(1, $countableOccurrences);
    }

    public function test_inherited_interfaces_should_not_add_dependencies(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace App;

        class MyClass extends \ArrayObject
        {
        }
        EOF;

        $cd = $this->parseCode($code);

        self::assertCount(1, $cd);

        // Dependencies should only include direct references (ArrayObject), not inherited interfaces
        $dependencyNames = array_map(
            static fn ($dep): string => $dep->getFQCN()->toString(),
            $cd[0]->getDependencies()
        );

        self::assertNotContains('IteratorAggregate', $dependencyNames);
        self::assertNotContains('Traversable', $dependencyNames);
        self::assertNotContains('ArrayAccess', $dependencyNames);
        self::assertNotContains('Countable', $dependencyNames);
    }

    public function test_interface_extending_another_should_resolve_parent_interfaces(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace App;

        interface MyInterface extends \IteratorAggregate
        {
        }
        EOF;

        $cd = $this->parseCode($code);

        self::assertCount(1, $cd);
        self::assertTrue($cd[0]->isInterface());

        $extends = array_map(
            static fn (FullyQualifiedClassName $fqcn): string => $fqcn->toString(),
            $cd[0]->getExtends()
        );

        // IteratorAggregate extends Traversable, so Traversable should be in extends
        self::assertContains('IteratorAggregate', $extends);
        self::assertContains('Traversable', $extends);
    }

    public function test_enum_implementing_interface_should_resolve_parent_interfaces(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace App;

        enum MyEnum implements \IteratorAggregate
        {
            case FOO;

            public function getIterator(): \Traversable
            {
                return new \ArrayIterator([]);
            }
        }
        EOF;

        $cd = $this->parseCode($code);

        self::assertCount(1, $cd);
        self::assertTrue($cd[0]->isEnum());

        $interfaces = array_map(
            static fn (FullyQualifiedClassName $fqcn): string => $fqcn->toString(),
            $cd[0]->getInterfaces()
        );

        // IteratorAggregate extends Traversable
        self::assertContains('IteratorAggregate', $interfaces);
        self::assertContains('Traversable', $interfaces);
    }

    public function test_class_extending_parent_should_resolve_ancestor_classes(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace App;

        class MyException extends \InvalidArgumentException
        {
        }
        EOF;

        $cd = $this->parseCode($code);

        self::assertCount(1, $cd);

        $extends = array_map(
            static fn (FullyQualifiedClassName $fqcn): string => $fqcn->toString(),
            $cd[0]->getExtends()
        );

        // InvalidArgumentException extends LogicException extends Exception
        self::assertContains('InvalidArgumentException', $extends);
        self::assertContains('LogicException', $extends);
        self::assertContains('Exception', $extends);
    }

    /**
     * @return array<ClassDescription>
     */
    private function parseCode(string $code): array
    {
        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_2);
        $fp->parse($code, 'relativePathName');

        return $fp->getClassDescriptions();
    }
}
