<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FileParser;
use Arkitect\Analyzer\FileParserFactory;
use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Expression\ForClasses\DependsOnlyOnTheseNamespaces;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Expression\ForClasses\IsAbstract;
use Arkitect\Expression\ForClasses\IsFinal;
use Arkitect\Expression\ForClasses\IsReadonly;
use Arkitect\Expression\ForClasses\NotHaveDependencyOutsideNamespace;
use Arkitect\Rules\ParsingError;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class FileVisitorTest extends TestCase
{
    public function test_should_parse_non_php_file(): void
    {
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.4'));
        $fp->parse('', 'path/to/class.php');

        self::assertEmpty($fp->getClassDescriptions());
    }

    public function test_should_parse_empty_file(): void
    {
        $code = <<< 'EOF'
        <?php
        EOF;

        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.4'));
        $fp->parse($code, 'path/to/class.php');

        self::assertEmpty($fp->getClassDescriptions());
    }

    public function test_violation_should_have_ref_to_filepath(): void
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

        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.4'));
        $fp->parse($code, 'path/to/class.php');

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['Foo']);
        $dependsOnTheseNamespaces->evaluate($fp->getClassDescriptions()[0], $violations, 'because');

        self::assertCount(2, $violations);
        self::assertEquals('path/to/class.php', $violations->get(0)->getFilePath());
        self::assertEquals('path/to/class.php', $violations->get(1)->getFilePath());
    }

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
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.4'));
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
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.4'));
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        self::assertCount(2, $cd);
        self::assertInstanceOf(ClassDescription::class, $cd[0]);
        self::assertInstanceOf(ClassDescription::class, $cd[1]);

        $expectedInterfaces = [
            new ClassDependency('Root\Namespace1\AnInterface', 7),
            new ClassDependency('Root\Namespace1\InterfaceTwo', 7),
            new ClassDependency('Root\Namespace1\Another\ForbiddenInterface', 11),
            new ClassDependency('Root\Namespace1\Proj', 23),
        ];

        self::assertEquals($expectedInterfaces, $cd[0]->getDependencies());
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
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.4'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions()[1];

        self::assertEquals('Root\Animals\Animal', $cd->getExtends()[0]->toString());
    }

    public function test_it_should_not_parse_extends_from_insider_anonymousclass(): void
    {
        $code = <<< 'EOF'
        <?php

        namespace Root\Animals;

        class Animal
        {
        }

        class Cat extends Animal
        {
            public function methodWithAnonymous(): void
            {
                $obj = new class extends \stdClass {};
            }
        }
        EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.4'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions()[1];

        self::assertEquals('Root\Animals\Animal', $cd->getExtends()[0]->toString());
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
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.4'));
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['Foo', 'Symfony', 'Doctrine']);
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(0, $violations);
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
        use Foo\Baz\Nullable;

        class MyClass implements Baz
        {
            public function __construct(Request $request, ?Nullable $nullable)
            {
                $collection = new Collection($request);
                $static = StaticClass::foo();
            }
        }
        EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.4'));
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $expectedDependencies = [
            new ClassDependency('Foo\Baz\Baz', 10),
            new ClassDependency('Symfony\Component\HttpFoundation\Request', 12),
            new ClassDependency('Foo\Baz\Nullable', 12),
            new ClassDependency('Doctrine\MongoDB\Collection', 14),
            new ClassDependency('Foo\Baz\StaticClass', 15),
        ];

        self::assertEquals($expectedDependencies, $cd[0]->getDependencies());
    }

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

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['Foo', 'Symfony', 'Doctrine']);
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(0, $violations);
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
        self::assertEquals([
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

        self::assertCount(0, $violations);
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

        self::assertCount(0, $violations);
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

        self::assertCount(1, $violations);
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

        self::assertCount(1, $violations);
    }

    public function test_it_can_parse_enum(): void
    {
        $code = <<< 'EOF'
<?php
namespace Root\Cars;
enum Enum
{
    case Hearts;
    case Diamonds;
    case Clubs;
    case Spades;
}
EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.1'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $notHaveDependencyOutsideNamespace = new Implement('MyInterface');
        $notHaveDependencyOutsideNamespace->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(1, $violations);
    }

    public function test_should_implement_exact_classname(): void
    {
        $code = <<< 'EOF'
<?php
namespace Foo;
interface Order
{
}

interface OrderTwo
{
}

class Test implements Order
{
}

EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.1'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions()[2]; // class Test

        $violations = new Violations();

        $implement = new Implement('Foo\Order');
        $implement->evaluate($cd, $violations, 'we want to add this rule for our software');

        self::assertCount(0, $violations);
    }

    public function test_it_parse_interfaces(): void
    {
        $code = <<< 'EOF'
<?php
namespace MyProject\AppBundle\Application;
use Doctrine\ORM\QueryBuilder;
interface BookRepositoryInterface
{
    public function getBookList(): QueryBuilder;
}
EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.1'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['MyProject\AppBundle\Application']);
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(1, $violations);
    }

    public function test_it_parse_interface_extends(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace MyProject\AppBundle\Application;

        interface FooAble
        {
            public function foo();
        }

        interface BarAble
        {
            public function bar();
        }


        interface ForBarAble extends FooAble, BarAble
        {
            public function foobar();
        }
        EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.1'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        self::assertCount(3, $cd);
        self::assertEquals('MyProject\AppBundle\Application\FooAble', $cd[2]->getExtends()[0]->toString());
        self::assertEquals('MyProject\AppBundle\Application\BarAble', $cd[2]->getExtends()[1]->toString());
    }

    public function test_it_handles_return_types(): void
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

    public function getRequest(): Request //the violations is reported here
    {
        return new Request();
    }
}
EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.4'));
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['Foo', 'Symfony', 'Doctrine']);
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(0, $violations);
    }

    public function test_it_parse_traits(): void
    {
        $code = <<< 'EOF'
<?php
namespace MyProject\AppBundle\Application;
use Doctrine\ORM\QueryBuilder;
trait BookRepositoryInterface
{
    public function getBookList(): QueryBuilder
    {

    }
}
EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.1'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['MyProject\AppBundle\Application']);
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(1, $violations);
    }

    /**
     * @dataProvider provide_enums
     */
    public function test_it_parse_enums(string $code): void
    {
        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.1'));
        $fp->parse($code, 'relativePathName');

        foreach ($fp->getClassDescriptions() as $classDescription) {
            self::assertTrue($classDescription->isEnum());
        }
    }

    public static function provide_enums(): \Generator
    {
        yield 'default enum' => [
            <<< 'EOF'
            <?php
            namespace App\Foo;

            enum DefaultEnum
            {
                case FOO;
            }
            EOF,
        ];

        yield 'string enum' => [
            <<< 'EOF'
            <?php
            namespace App\Foo;

            enum StringEnum: string
            {
                case BAR: 'bar';
            }
            EOF,
        ];

        yield 'integer enum' => [
            <<< 'EOF'
            <?php
            namespace App\Foo;

            enum IntEnum: int
            {
                case BAZ: 42;
            }
            EOF,
        ];

        yield 'multiple enums' => [
            <<< 'EOF'
            <?php
            namespace App\Foo;

            enum DefaultEnum
            {
                case FOO;
            }

            enum IntEnum: int
            {
                case BAZ: 42;
            }

            enum IntEnum: int
            {
                case BAZ: 42;
            }
            EOF,
        ];
    }

    public function test_is_final_when_there_is_anonymous_final(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace App\Foo;

        final class User {
            public function __construct() {
               $class = new class() extends Bundle {}
            }
        }
        EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.4'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();
        $violations = new Violations();
        $isFinal = new IsFinal();
        $isFinal->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(0, $violations);
    }

    public function test_is_abstract_when_there_is_anonymous_final(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace App\Foo;

        abstract class User {
            public function bar() {
                $class = new class() extends Bundle {}
            }

            abstract public function foo() {}
        }
        EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.4'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();
        $violations = new Violations();
        $isAbstract = new IsAbstract();
        $isAbstract->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(0, $violations);
    }

    public function test_is_readonly_when_there_is_anonymous_final(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace App\Foo;

         readonly class User {
            public function __construct() {
               $class = new class() extends Bundle {}
            }
        }
        EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.4'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();
        $violations = new Violations();
        $isReadOnly = new IsReadonly();
        $isReadOnly->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(0, $violations);
    }
}
