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
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Expression\ForClasses\NotContainDocBlockLike;
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

        self::assertCount(2, $cd);
        self::assertInstanceOf(ClassDescription::class, $cd[0]);
        self::assertInstanceOf(ClassDescription::class, $cd[1]);

        $expectedInterfaces = [
            new ClassDependency('Root\Namespace1\AnInterface', 7),
            new ClassDependency('Root\Namespace1\InterfaceTwo', 7),
            new ClassDependency('Root\Namespace1\Another\ForbiddenInterface', 11),
            new ClassDependency('Root\Namespace1\Proj', 23),
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
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.1'));
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $expectedDependencies = [
            new ClassDependency('Foo\Baz\Baz', 10),
            new ClassDependency('Symfony\Component\HttpFoundation\Request', 12),
            new ClassDependency('Foo\Baz\Nullable', 12),
            new ClassDependency('Doctrine\MongoDB\Collection', 14),
            new ClassDependency('Foo\Baz\StaticClass', 15),
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

        $this->assertCount(1, $violations);
    }

    /**
     * @requires PHP >= 8.1
     */
    public function test_should_parse_enum_attributes(): void
    {
        $code = <<< 'EOF'
            <?php
            namespace Root\Cars;
            use Bar\FooAttr;
            #[FooAttr('bar')]
            #[Baz]
            enum Enum
            {
                case Hearts;
                case Diamonds;
                case Clubs;
                case Spades;
            }
            EOF;

        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.1'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        self::assertEquals(
            [
                FullyQualifiedClassName::fromString('Bar\\FooAttr'),
                FullyQualifiedClassName::fromString('Root\\Cars\\Baz'),
            ],
            $cd[0]->getAttributes()
        );
    }

    public function test_it_parse_docblocks(): void
    {
        $code = <<< 'EOF'
            <?php
            namespace Root\Cars;

            /**
            * @throws Exception
            */
            class Bar
            {
                 /**
            	 * @throws ItemNotFound
            	 */
                public function getFoo(): int
                {
                    return 1;
                }
            }
            EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.1'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $notHaveDependencyOutsideNamespace = new NotContainDocBlockLike('ItemNotFound');
        $notHaveDependencyOutsideNamespace->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(1, $violations);

        $notHaveDependencyOutsideNamespace = new NotContainDocBlockLike('Exception');
        $notHaveDependencyOutsideNamespace->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(2, $violations);
    }

    public function test_it_parse_typed_property(): void
    {
        $code = <<< 'EOF'
            <?php
            namespace MyProject\AppBundle\Application;

            use Symfony\Component\Validator\Constraints\NotBlank;

            class ApplicationLevelDto
            {
                public NotBlank $foo;
            }
            EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.1'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $notHaveDependencyOutsideNamespace = new DependsOnlyOnTheseNamespaces('MyProject\AppBundle\Application');
        $notHaveDependencyOutsideNamespace->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(1, $violations);
    }

    public function test_it_parse_typed_nullable_property(): void
    {
        $code = <<< 'EOF'
            <?php
            namespace MyProject\AppBundle\Application;

            use Symfony\Component\Validator\Constraints\NotBlank;

            class ApplicationLevelDto
            {
                public ?NotBlank $foo;
            }
            EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.1'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $notHaveDependencyOutsideNamespace = new DependsOnlyOnTheseNamespaces('MyProject\AppBundle\Application');
        $notHaveDependencyOutsideNamespace->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(1, $violations);
    }

    public function test_it_parse_scalar_typed_property(): void
    {
        $code = <<< 'EOF'
            <?php
            namespace MyProject\AppBundle\Application;
            class ApplicationLevelDto
            {
                public bool $fooBool;
                public int $fooInt;
                public float $fooFloat;
                public string $fooString;
            }
            EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.1'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $notHaveDependencyOutsideNamespace = new NotHaveDependencyOutsideNamespace('MyProject\AppBundle\Application');
        $notHaveDependencyOutsideNamespace->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(0, $violations);
    }

    public function test_it_parse_nullable_scalar_typed_property(): void
    {
        $code = <<< 'EOF'
            <?php
            namespace MyProject\AppBundle\Application;
            class ApplicationLevelDto
            {
                public function __construct(
                    ?bool $fooBool,
                    ?int $fooInt,
                    ?float $fooFloat,
                    ?string $fooString
                ) {

                }
            }
            EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.1'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $notHaveDependencyOutsideNamespace = new NotHaveDependencyOutsideNamespace('MyProject\AppBundle\Application');
        $notHaveDependencyOutsideNamespace->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(0, $violations);
    }

    public function test_it_parse_dependencies_in_docblocks_customs(): void
    {
        $code = <<< 'EOF'
            <?php
            namespace MyProject\AppBundle\Application;
            use Symfony\Component\Validator\Constraints\NotBlank;
            class ApplicationLevelDto
            {
            /**
            * @var NotBlank
            */
                 public $foo;

            /**
            * @var array<int, \stdClass>
            */
                 public $baz;
            }
            EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.1'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $notHaveDependencyOutsideNamespace = new DependsOnlyOnTheseNamespaces('MyProject\AppBundle\Application');
        $notHaveDependencyOutsideNamespace->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(1, $violations);
    }

    public function test_it_parse_custom_tags_in_docblocks(): void
    {
        $code = <<< 'EOF'
            <?php
            namespace MyProject\AppBundle\Application;
            use Symfony\Component\Validator\Constraints as Assert;
            class ApplicationLevelDto
            {
            /**
            * @Assert\NotBlank
            */
                 public $foo;

            }
            EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.1'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $notHaveDependencyOutsideNamespace = new DependsOnlyOnTheseNamespaces('MyProject\AppBundle\Application');
        $notHaveDependencyOutsideNamespace->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(1, $violations);
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
            class test implements Order
            {
            }
            EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.1'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $implement = new Implement('Foo\Order');
        $implement->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(0, $violations, $violations->toString());
    }

    public function test_it_parse_dependencies_in_docblocks_with_alias(): void
    {
        $code = <<< 'EOF'
            <?php
            namespace MyProject\AppBundle\Application;
            use Symfony\Component\Validator\Constraints as Assert;
            use Symfony\Test;
            class ApplicationLevelDto
            {
                /**
                 * @Assert\NotBlank
                 */
                public string|null $foo;
            }
            EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.1'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces('MyProject\AppBundle\Application');
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(1, $violations);
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

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces('MyProject\AppBundle\Application');
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(1, $violations);
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
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.1'));
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces('Foo', 'Symfony', 'Doctrine');
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(0, $violations);
    }

    public function test_it_skip_custom_annotations_in_docblocks_if_the_option_parse_custom_annotation_is_false(): void
    {
        $code = <<< 'EOF'
            <?php
            namespace MyProject\AppBundle\Application;
            use Symfony\Component\Validator\Constraints as Assert;
            class ApplicationLevelDto
            {
            /**
            * @Assert\NotBlank
            */
                 public $foo;
            }
            EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.1'), false);
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnlyOnTheseNamespaces = new DependsOnlyOnTheseNamespaces('MyProject\AppBundle\Application');
        $dependsOnlyOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(0, $violations);
    }

    public function test_it_parse_arrays_as_scalar_types(): void
    {
        $code = <<< 'EOF'
            <?php
            namespace App\Domain;
            Class MyClass
            {
                private array $field1;
                public function __construct(array $field1)
                {
                    $this->field1 = $field1;
                }
            }
            EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.1'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $notHaveDependenciesOutside = new NotHaveDependencyOutsideNamespace('App\Domain');
        $notHaveDependenciesOutside->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(0, $violations);
    }

    public function test_it_handles_typed_arrays_in_properties_with_generics_syntax(): void
    {
        $code = <<< 'EOF'
            <?php
            namespace Domain\Foo;

            use Application\MyDto;

            class MyClass
            {
                /**
                 * @var array<int, MyDto>
                 */
                private array $dtoList;
            }
            EOF;

        /** @noinspection PhpUnhandledExceptionInspection */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.1'));
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces('Domain');
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(1, $violations);
    }

    public function test_it_handles_typed_arrays_in_properties_with_list_syntax(): void
    {
        $code = <<< 'EOF'
            <?php
            namespace Domain\Foo;

            use Application\MyDto;

            class MyClass
            {
                /**
                 * @var list<MyDto>
                 */
                private array $dtoList;
            }
            EOF;

        /** @noinspection PhpUnhandledExceptionInspection */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.1'));
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces('Domain');
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(1, $violations);
    }

    public function test_it_handles_typed_arrays_in_properties_with_legacy_syntax(): void
    {
        $code = <<< 'EOF'
            <?php
            namespace Domain\Foo;

            use Application\MyDto;

            class MyClass
            {
                /**
                 * @var MyDto[]
                 */
                private array $dtoList;
            }
            EOF;

        /** @noinspection PhpUnhandledExceptionInspection */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.1'));
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces('Domain');
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(1, $violations);
    }

    public function test_it_handles_typed_arrays_in_method_params_with_generics_syntax(): void
    {
        $code = <<< 'EOF'
            <?php
            namespace Domain\Foo;

            use Application\MyDto;

            class MyClass
            {
                /**
                 * @param array<int, MyDto> $dtoList
                 */
                public function __construct(array $dtoList)
                {
                }
            }
            EOF;

        /** @noinspection PhpUnhandledExceptionInspection */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.1'));
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces('Domain');
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(1, $violations);
    }

    public function test_it_handles_typed_arrays_in_method_params_with_list_syntax(): void
    {
        $code = <<< 'EOF'
            <?php
            namespace Domain\Foo;

            use Application\MyDto;

            class MyClass
            {
                /**
                 * @param list<MyDto> $dtoList
                 */
                public function __construct(array $dtoList)
                {
                }
            }
            EOF;

        /** @noinspection PhpUnhandledExceptionInspection */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.1'));
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces('Domain');
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(1, $violations);
    }

    public function test_it_handles_typed_arrays_in_method_params_with_legacy_syntax(): void
    {
        $code = <<< 'EOF'
            <?php
            namespace Domain\Foo;

            use Application\MyDto;

            class MyClass
            {
                /**
                 * @param MyDto[] $dtoList
                 */
                public function __construct(array $dtoList)
                {
                }
            }
            EOF;

        /** @noinspection PhpUnhandledExceptionInspection */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.1'));
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces('Domain');
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(1, $violations);
    }

    public function test_it_handles_typed_arrays_in_method_params_with_multiple_params(): void
    {
        $code = <<< 'EOF'
            <?php
            namespace Domain\Foo;

            use Application\MyDto;
            use Domain\ValueObject;

            class MyClass
            {
                /**
                 * @param MyDto[] $dtoList
                 * @param int $var2
                 * @param ValueObject[] $voList
                 */
                public function __construct(string $var1, array $dtoList, $var2, array $voList)
                {
                }
            }
            EOF;

        /** @noinspection PhpUnhandledExceptionInspection */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.1'));
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces('Domain');
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(1, $violations);
    }

    public function test_it_handles_typed_arrays_in_return_type_with_generics_syntax(): void
    {
        $code = <<< 'EOF'
            <?php
            namespace Domain\Foo;

            use Application\MyDto;

            class MyClass
            {
                /**
                 * @return array<int, MyDto>
                 */
                public function getList(): array
                {
                    return [];
                }
            }
            EOF;

        /** @noinspection PhpUnhandledExceptionInspection */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.1'));
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces('Domain');
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(1, $violations);
    }

    public function test_it_handles_typed_arrays_in_return_type_with_list_syntax(): void
    {
        $code = <<< 'EOF'
            <?php
            namespace Domain\Foo;

            use Application\MyDto;

            class MyClass
            {
                /**
                 * @return list<MyDto>
                 */
                public function getList(): array
                {
                    return [];
                }
            }
            EOF;

        /** @noinspection PhpUnhandledExceptionInspection */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.1'));
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces('Domain');
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(1, $violations);
    }

    public function test_it_handles_typed_arrays_in_return_type_with_legacy_syntax(): void
    {
        $code = <<< 'EOF'
            <?php
            namespace Domain\Foo;

            use Application\MyDto;

            class MyClass
            {
                /**
                 * @return MyDto[]
                 */
                public function getList(): array
                {
                    return [];
                }
            }
            EOF;

        /** @noinspection PhpUnhandledExceptionInspection */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('7.1'));
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces('Domain');
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(1, $violations);
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

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces('MyProject\AppBundle\Application');
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        $this->assertCount(1, $violations);
    }

    /**
     * @requires PHP >= 8.1
     *
     * @dataProvider provide_enums
     */
    public function test_it_parse_enums(string $code): void
    {
        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.1'));
        $fp->parse($code, 'relativePathName');

        foreach ($fp->getClassDescriptions() as $classDescription) {
            $this->assertTrue($classDescription->isEnum());
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
}
