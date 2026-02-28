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
        $cd = $this->parseFile(__DIR__.'/../../../Fixtures/Inheritance/MyArrayObject.php');

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
        $cd = $this->parseFile(__DIR__.'/../../../Fixtures/Inheritance/MyIterator.php');

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

        class NonExistentChild extends NonExistent\ParentClass
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

        self::assertStringContainsString('autoloaded', $allErrors);
    }

    public function test_inherited_interfaces_should_not_duplicate_direct_interfaces(): void
    {
        $cd = $this->parseFile(__DIR__.'/../../../Fixtures/Inheritance/MyCountableArrayObject.php');

        self::assertCount(1, $cd);

        $interfaces = array_map(
            static fn (FullyQualifiedClassName $fqcn): string => $fqcn->toString(),
            $cd[0]->getInterfaces()
        );

        // Countable should appear only once (ReflectionClass::getInterfaceNames() deduplicates)
        $countableOccurrences = array_count_values($interfaces)['Countable'] ?? 0;
        self::assertEquals(1, $countableOccurrences);
    }

    public function test_inherited_php_core_interfaces_should_be_filtered_from_dependencies(): void
    {
        $cd = $this->parseFile(__DIR__.'/../../../Fixtures/Inheritance/MyArrayObject.php');

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
        $cd = $this->parseFile(__DIR__.'/../../../Fixtures/Inheritance/MyInterface.php');

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
        $cd = $this->parseFile(__DIR__.'/../../../Fixtures/Inheritance/MyEnum.php');

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
        $cd = $this->parseFile(__DIR__.'/../../../Fixtures/Inheritance/MyException.php');

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
    private function parseFile(string $filePath): array
    {
        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_2);
        $fp->parse(file_get_contents($filePath), $filePath);

        return $fp->getClassDescriptions();
    }
}
