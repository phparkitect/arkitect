<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FileParser;
use Arkitect\Analyzer\FileParserFactory;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Expression\ForClasses\DependsOnlyOnTheseNamespaces;
use Arkitect\Expression\ForClasses\NotHaveDependencyOutsideNamespace;
use Arkitect\Rules\ParsingError;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class FileVisitorTest extends TestCase
{
    public function test_should_create_a_class_description(): void
    {
        $code = <<< 'EOF'
<?php

namespace Root\Namespace1;

use Root\Namespace2\D;

class Dog implements AnInterface, InterfaceTwo
{
}

class Cat implements AnInterface
{

}
EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.1'));
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        self::assertCount(2, $cd);
        self::assertInstanceOf(ClassDescription::class, $cd[0]);
        self::assertInstanceOf(ClassDescription::class, $cd[1]);
    }

    public function test_should_create_a_class_description_and_parse_anonymous_class(): void
    {
        $code = <<< 'EOF'
<?php

namespace Root\Namespace1;

use Root\Namespace2\D;

class Dog implements AnInterface, InterfaceTwo
{
    public function foo()
    {
        $projector2 = new class() implements Another\ForbiddenInterface
            {
                public function applyDummyDomainEvent(int $anInteger): void
                {
                }

                public function getEventsTypes(): string
                {
                    return "";
                }
            };

            $projector = new Proj();
    }
}

class Cat implements AnInterface
{

}
EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.1'));
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        self::assertCount(3, $cd);
        self::assertInstanceOf(ClassDescription::class, $cd[0]);
        self::assertInstanceOf(ClassDescription::class, $cd[1]);

        $expectedInterfaces = [
            new ClassDependency('Root\Namespace1\AnInterface', 7),
            new ClassDependency('Root\Namespace1\InterfaceTwo', 7),
            new ClassDependency('Root\Namespace1\Another\ForbiddenInterface', 11),
        ];

        $this->assertEquals($expectedInterfaces, $cd[0]->getDependencies());
    }

    public function test_it_should_parse_extends_class(): void
    {
        $code = <<< 'EOF'
<?php

namespace Root\Animals;

class Animal
{
}

class Cat extends Animal
{

}
EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.1'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions()[1];

        $this->assertEquals('Root\Animals\Animal', $cd->getExtends()->toString());
    }

    public function test_should_depends_on_these_namespaces(): void
    {
        $code = <<< 'EOF'
<?php
namespace Foo\Bar;

use Doctrine\MongoDB\Collection;
use Foo\Baz\Baz;
use Symfony\Component\HttpFoundation\Request;

class MyClass implements Baz
{
    public function __construct(Request $request)
    {
        $collection = new Collection($request);
    }
}
EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.1'));
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces('Foo', 'Symfony', 'Doctrine');
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

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

class MyClass implements Baz
{
    public function __construct(Request $request)
    {
        $collection = new Collection($request);
        $static = StaticClass::foo();
    }
}
EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.1'));
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $expectedDependencies = [
            new ClassDependency('Foo\Baz\Baz', 9),
            new ClassDependency('Symfony\Component\HttpFoundation\Request', 11),
            new ClassDependency('Doctrine\MongoDB\Collection', 13),
            new ClassDependency('Foo\Baz\StaticClass', 14),
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

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.4'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces('Foo', 'Symfony', 'Doctrine');
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(0, $violations);
    }

    public function test_it_should_catch_parsing_errors(): void
    {
        $code = <<< 'EOF'
<?php

namespace Root\Animals;

class Animal
{
    public function __construct()
    {
       FOO
    }
}
EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.4'));
        $fp->parse($code, 'relativePathName');

        $parsingErrors = $fp->getParsingErrors();
        $this->assertEquals([
            ParsingError::create('relativePathName', 'Syntax error, unexpected \'}\' on line 10'),
        ], $parsingErrors);
    }

    public function test_null_class_description_builder(): void
    {
        $code = <<< 'EOF'
<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Quote\Quote;

interface QuoteCommandInterface
{
    /**
     * Save a stock quote.
     */
    public function save(Quote $quote): void;
}
EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.4'));
        $fp->parse($code, 'relativePathName');

        $violations = new Violations();

        $this->assertCount(0, $violations);
    }

    public function test_it_should_parse_self_correctly(): void
    {
        $code = <<< 'EOF'
<?php

namespace Root\Animals;

class Tiger extends Animal
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

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.4'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $notHaveDependencyOutsideNamespace = new NotHaveDependencyOutsideNamespace('Root\Animals');
        $notHaveDependencyOutsideNamespace->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(0, $violations);
    }

    public function test_it_should_return_errors_for_class_outside_namespace(): void
    {
        $code = <<< 'EOF'
<?php

namespace MyNamespace\MyClasses;

use AnotherNamespace\Baz;

class Foo
{
    public function foo()
    {
        $bar = new Bar();
        $baz = new Baz();
    }
}
EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.4'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnlyOnTheseNamespaces = new DependsOnlyOnTheseNamespaces();
        $dependsOnlyOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(1, $violations);
    }

    /**
     * @requires PHP >= 8.0
     */
    public function test_should_parse_class_attributes(): void
    {
        $code = <<< 'EOF'
<?php

use Bar\FooAttr;

#[FooAttr('bar')]
#[Baz]
class Foo {}
EOF;

        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.0'));
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        self::assertEquals(
            [
                FullyQualifiedClassName::fromString('Bar\\FooAttr'),
                FullyQualifiedClassName::fromString('Baz'),
            ],
            $cd[0]->getAttributes()
        );
    }

    public function test_it_should_return_errors_for_const_outside_namespace(): void
    {
        $code = <<< 'EOF'
<?php
namespace Root\Cars;
use AnotherNamespace\CarMake;
class KiaSportage extends AbstractCar
{
    public function __construct()
    {
        parent::__construct(CarMake::KIA, 'Sportage');
    }

    public function getSelf(): self
    {
        return self::class;
    }

    public function getStatic(): self
    {
        return static::class;
    }
}
EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.4'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $notHaveDependencyOutsideNamespace = new NotHaveDependencyOutsideNamespace('Root\Cars');
        $notHaveDependencyOutsideNamespace->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(1, $violations);
    }
}
