<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\ClassDescriptionCollection;
use Arkitect\Analyzer\FileParser;
use Arkitect\Analyzer\FileVisitor;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Expression\ForClasses\DependsOnlyOnTheseNamespaces;
use Arkitect\Expression\ForClasses\NotHaveDependencyOutsideNamespace;
use Arkitect\Rules\ParsingError;
use Arkitect\Rules\ParsingErrors;
use Arkitect\Rules\Violations;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PHPUnit\Framework\TestCase;

class FileVisitorTest extends TestCase
{
    public function test_should_create_a_class_description_and_parse_anonymous_class(): void
    {
        $code = <<< 'EOF'
<?php

namespace Root\Namespace1;

use Root\Namespace2\AnInterface;
use Root\Namespace2\InterfaceTwo;
use Another\ForbiddenInterface;

class Dog implements AnInterface, InterfaceTwo
{
    public function foo()
    {
        $projector2 = new class() implements ForbiddenInterface
            {
                public function applyDummyDomainEvent(int $anInteger): void
                {
                }

                public function getEventsTypes(): string
                {
                    return "";
                }
            };
    }
}
EOF;

        $fileContentGetter = new FakeFileContentGetter();
        $classDescription1 = new ClassDescription(
            FullyQualifiedClassName::fromString('Root\Namespace2\Dog'),
            [
                new ClassDependency('Another\ForbiddenInterface', 13),
                new ClassDependency('Root\Namespace2\AnInterface', 9),
                new ClassDependency('Root\Namespace2\InterfaceTwo', 9),
            ],
            [],
            null
        );
        $classDescription2 = new ClassDescription(
            FullyQualifiedClassName::fromString('Another\ForbiddenInterface'),
            [],
            [],
            null
        );
        $classDescription3 = new ClassDescription(
            FullyQualifiedClassName::fromString('Root\Namespace2\AnInterface'),
            [],
            [],
            null
        );
        $classDescription4 = new ClassDescription(
            FullyQualifiedClassName::fromString('Root\Namespace2\InterfaceTwo'),
            [],
            [],
            null
        );

        $classDescriptionCollection = new ClassDescriptionCollection();
        $classDescriptionCollection->add($classDescription1);
        $classDescriptionCollection->add($classDescription2);
        $classDescriptionCollection->add($classDescription3);
        $classDescriptionCollection->add($classDescription4);
        $fp = new FileParser(
            new NodeTraverser(),
            new FileVisitor(),
            new NameResolver(),
            TargetPhpVersion::create('7.1'),
            $fileContentGetter
        );
        /** @var ClassDescription $cd */
        $cd = $fp->parse($code, 'relativePathName', [], new ParsingErrors());

        $expectedInterfaces = [
            'Root\Namespace2\AnInterface' => new ClassDependency('Root\Namespace2\AnInterface', 9),
            'Root\Namespace2\InterfaceTwo' => new ClassDependency('Root\Namespace2\InterfaceTwo', 9),
            'Another\ForbiddenInterface' => new ClassDependency('Another\ForbiddenInterface', 13),
        ];

        $this->assertEquals($expectedInterfaces, $cd[0]->getDependencies());
    }

    public function test_it_should_parse_extends_class(): void
    {
        $code = <<< 'EOF'
<?php

namespace Root\Animals;

class Feline
{
}

class Cat extends Feline
{

}
EOF;

        $fileContentGetter = new FakeFileContentGetter();
        $classDescription1 = new ClassDescription(
            FullyQualifiedClassName::fromString('Root\Animals\Feline'),
            [],
            [],
            null
        );
        $classDescription2 = new ClassDescription(
            FullyQualifiedClassName::fromString('Root\Animals\Cat'),
            [],
            [],
            FullyQualifiedClassName::fromString('Root\Animals\Feline')
        );
        $classDescriptionCollection = new ClassDescriptionCollection();
        $classDescriptionCollection->add($classDescription1);
        $classDescriptionCollection->add($classDescription2);

        /** @var FileParser $fp */
        $fp = new FileParser(
            new NodeTraverser(),
            new FileVisitor(),
            new NameResolver(),
            TargetPhpVersion::create('7.1'),
            $fileContentGetter
        );
        $cd = $fp->parse($code, 'relativePathName', [], new ParsingErrors())[1];

        $this->assertEquals('Root\Animals\Feline', $cd->getExtends()->toString());
    }

    public function test_should_depends_on_these_namespaces(): void
    {
        $code = <<< 'EOF'
<?php
namespace Foo\Bar;

use Symfony\Component\HttpFoundation\Request;

class MyClass
{
    public function __construct(Request $request)
    {
    }
}
EOF;

        $fileContentGetter = new FakeFileContentGetter();
        $classDescription1 = new ClassDescription(
            FullyQualifiedClassName::fromString('Foo\Bar\MyClass'),
            [new ClassDependency('Symfony\Component\HttpFoundation\Request', 10)],
            [],
            null
        );

        $classDescription2 = new ClassDescription(
            FullyQualifiedClassName::fromString('Symfony\Component\HttpFoundation\Request'),
            [],
            [],
            null
        );

        $classDescriptionCollection = new ClassDescriptionCollection();
        $classDescriptionCollection->add($classDescription1);
        $classDescriptionCollection->add($classDescription2);

        $fp = new FileParser(
            new NodeTraverser(),
            new FileVisitor(),
            new NameResolver(),
            TargetPhpVersion::create('7.1'),
            $fileContentGetter
        );
        $cd = $fp->parse($code, 'relativePathName', [], new ParsingErrors());

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces('Symfony');
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, $classDescriptionCollection);

        $this->assertCount(0, $violations);
    }

    public function test_should_returns_all_dependencies(): void
    {
        $code = <<< 'EOF'
<?php
namespace Foo\Bar;

use Doctrine\MongoDB\Collection;
use Foo\Baz\Baz;
use Symfony\Component\HttpFoundation\Request;
use Foo\Baz\StaticClass;

class MyClassShouldReturnAllDependencies implements Baz
{
    public function __construct(Request $request)
    {
        $collection = new Collection($request);
        $static = StaticClass::foo();
    }
}
EOF;

        $fileContentGetter = new FakeFileContentGetter();
        $classDescription1 = new ClassDescription(
            FullyQualifiedClassName::fromString('Foo\Bar\MyClassShouldReturnAllDependencies'),
            [
                new ClassDependency('Doctrine\MongoDB\Collection', 10),
                new ClassDependency('Foo\Baz\Baz', 11),
                new ClassDependency('Foo\Baz\StaticClass', 12),
                new ClassDependency('Symfony\Component\HttpFoundation\Request', 13),
            ],
            [],
            null
        );

        $classDescription2 = new ClassDescription(
            FullyQualifiedClassName::fromString('Symfony\Component\HttpFoundation\Request'),
            [],
            [],
            null
        );
        $classDescription3 = new ClassDescription(
            FullyQualifiedClassName::fromString('Doctrine\MongoDB\Collection'),
            [],
            [],
            null
        );
        $classDescription4 = new ClassDescription(
            FullyQualifiedClassName::fromString('Foo\Baz\Baz'),
            [],
            [],
            null
        );
        $classDescription5 = new ClassDescription(
            FullyQualifiedClassName::fromString('Foo\Baz\StaticClass'),
            [],
            [],
            null
        );

        $classDescriptionCollection = new ClassDescriptionCollection();
        $classDescriptionCollection->add($classDescription1);
        $classDescriptionCollection->add($classDescription2);
        $classDescriptionCollection->add($classDescription3);
        $classDescriptionCollection->add($classDescription4);
        $classDescriptionCollection->add($classDescription5);
        /** @var FileParser $fp */
        $fp = new FileParser(
            new NodeTraverser(),
            new FileVisitor(),
            new NameResolver(),
            TargetPhpVersion::create('7.1'),
            $fileContentGetter
        );
        $cd = $fp->parse($code, 'relativePathName', [], new ParsingErrors());

        $expectedDependencies = [
            'Foo\Baz\Baz' => new ClassDependency('Foo\Baz\Baz', 9),
            'Symfony\Component\HttpFoundation\Request' => new ClassDependency('Symfony\Component\HttpFoundation\Request', 11),
            'Doctrine\MongoDB\Collection' => new ClassDependency('Doctrine\MongoDB\Collection', 13),
            'Foo\Baz\StaticClass' => new ClassDependency('Foo\Baz\StaticClass', 14),
        ];

        $this->assertEquals($expectedDependencies, $cd[0]->getDependencies());
    }

    /**
     * @requires PHP >= 7.4
     */
    public function test_it_should_parse_arrow_function(): void
    {
        $code = <<< 'EOF'
<?php

namespace Root\Animals;

class Animal
{
    public function __construct()
    {
        $y = 1;
        $fn1 = fn($x) => $x + $y;
    }
}
EOF;
        $classDescription1 = new ClassDescription(
            FullyQualifiedClassName::fromString('Root\Animals\Animal'),
            [],
            [],
            null
        );

        $classDescriptionCollection = new ClassDescriptionCollection();
        $classDescriptionCollection->add($classDescription1);

        $fileContentGetter = new FakeFileContentGetter();
        /** @var FileParser $fp */
        $fp = new FileParser(
            new NodeTraverser(),
            new FileVisitor(),
            new NameResolver(),
            TargetPhpVersion::create('7.4'),
            $fileContentGetter
        );
        $cd = $fp->parse($code, 'relativePathName', [], new ParsingErrors());

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces('Foo', 'Symfony', 'Doctrine');
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, $classDescriptionCollection);

        $this->assertCount(0, $violations);
    }

    public function test_it_should_catch_parsing_errors(): void
    {
        $code = <<< 'EOF'
<?php

namespace Foo\Bar;

class BrokenClass
{
    public function __construct()
    {
       FOO
    }
}
EOF;

        $classDescription1 = new ClassDescription(
            FullyQualifiedClassName::fromString('Foo\Bar\BrokenClass'),
            [],
            [],
            null
        );

        $classDescriptionCollection = new ClassDescriptionCollection();
        $classDescriptionCollection->add($classDescription1);

        $fileContentGetter = new FakeFileContentGetter();
        $fp = new FileParser(
            new NodeTraverser(),
            new FileVisitor(),
            new NameResolver(),
            TargetPhpVersion::create('7.4'),
            $fileContentGetter
        );
        $fp->parse($code, 'relativePathName', [], new ParsingErrors());

        $parsingErrors = $fp->getParsingErrors();

        $expected = new ParsingErrors();
        $expected->add(ParsingError::create('relativePathName', 'Syntax error, unexpected \'}\' on line 10'));

        $this->assertEquals($expected, $parsingErrors);
    }

    public function test_it_should_parse_self_correctly(): void
    {
        $code = <<< 'EOF'
<?php

namespace Root\Animals;

class Tiger extends Feline
{
    public function foo()
    {
       self::bar();
       static::bar();
       parent::baz();
    }
    public static function bar()
    {
    }
    public function doSomething(self $self, static $static)
    {
    }
}
EOF;

        $classDescription1 = new ClassDescription(
            FullyQualifiedClassName::fromString('Root\Animals\Tiger'),
            [
                new ClassDependency('Root\Animals\Feline', 10),
            ],
            [],
            null
        );

        $classDescription2 = new ClassDescription(
            FullyQualifiedClassName::fromString('Root\Animals\Feline'),
            [],
            [],
            null
        );

        $classDescriptionCollection = new ClassDescriptionCollection();
        $classDescriptionCollection->add($classDescription1);
        $classDescriptionCollection->add($classDescription2);

        $fileContentGetter = new FakeFileContentGetter();
        $fp = new FileParser(
            new NodeTraverser(),
            new FileVisitor(),
            new NameResolver(),
            TargetPhpVersion::create('7.4'),
            $fileContentGetter
        );
        $cd = $fp->parse($code, 'relativePathName', [], new ParsingErrors());

        $violations = new Violations();

        $notHaveDependencyOutsideNamespace = new NotHaveDependencyOutsideNamespace('Root\Animals');
        $notHaveDependencyOutsideNamespace->evaluate($cd[0], $violations, $classDescriptionCollection);

        $this->assertCount(0, $violations);
    }
}
