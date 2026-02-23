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

    public function test_class_with_non_autoloadable_parent_should_report_parsing_error(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace App;

        class MyClass extends NonExistent\ParentClass
        {
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_2);
        $fp->parse($code, 'relativePathName');

        $parsingErrors = $fp->getParsingErrors();

        self::assertGreaterThan(0, \count($parsingErrors));

        $errorMessages = array_map(
            static fn ($error): string => $error->getError(),
            $parsingErrors
        );
        $allErrors = implode(' ', $errorMessages);

        self::assertStringContainsString('App\NonExistent\ParentClass', $allErrors);
        self::assertStringContainsString('autoloaded', $allErrors);
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

    public function test_inherited_php_core_interfaces_should_be_filtered_from_dependencies(): void
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

        // PHP core interfaces are filtered out by isPhpCoreClass, same as any other core dependency
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
