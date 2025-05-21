<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FileParserFactory;
use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Expression\ForClasses\DependsOnlyOnTheseNamespaces;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Expression\ForClasses\IsAbstract;
use Arkitect\Expression\ForClasses\IsFinal;
use Arkitect\Expression\ForClasses\IsReadonly;
use Arkitect\Expression\ForClasses\NotHaveDependencyOutsideNamespace;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class CanParseClassTest extends TestCase
{
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

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_7_4);
        $fp->parse($code, 'path/to/class.php');

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['Foo']);
        $dependsOnTheseNamespaces->evaluate($fp->getClassDescriptions()[0], $violations, 'because');

        self::assertCount(2, $violations);
        self::assertEquals('path/to/class.php', $violations->get(0)->getFilePath());
        self::assertEquals('path/to/class.php', $violations->get(1)->getFilePath());
    }

    public function test_should_parse_instanceof(): void
    {
        $code = <<< 'EOF'
        <?php

        class Foo
        {
            public function bar($a, $b)
            {
                $is_var = $a instanceof $b;

                $is_myclass = $a instanceof \Foo\Bar\MyClass;

                $is_another = $a instanceof self;
            }
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_7_4);
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        self::assertCount(1, $cd);
        self::assertCount(1, $cd[0]->getDependencies());
        self::assertEquals('Foo\Bar\MyClass', $cd[0]->getDependencies()[0]->getFQCN()->toString());
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

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_7_4);
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

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_7_4);
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

    public function test_should_parse_anonymous_class_extends(): void
    {
        $code = <<< 'EOF'
        <?php

        namespace Root\Namespace1;

        use Root\Namespace2\D;

        class Dog implements AnInterface, InterfaceTwo
        {
            public function foo()
            {
                $projector2 = new class() extends Another\ForbiddenExtend {};

            }
        }

        class Cat implements AnInterface
        {

        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_7_4);
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        self::assertCount(2, $cd);
        self::assertInstanceOf(ClassDescription::class, $cd[0]);
        self::assertInstanceOf(ClassDescription::class, $cd[1]);

        $expectedInterfaces = [
            new ClassDependency('Root\Namespace1\AnInterface', 7),
            new ClassDependency('Root\Namespace1\InterfaceTwo', 7),
            new ClassDependency('Root\Namespace1\Another\ForbiddenExtend', 11),
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

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_7_4);
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

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_7_4);
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

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_7_4);
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

                $self_static = self::foo();
            }
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_7_4);
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

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_7_4);
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['Foo', 'Symfony', 'Doctrine']);
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

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

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_7_4);
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

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_7_4);
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

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_7_4);
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $notHaveDependencyOutsideNamespace = new NotHaveDependencyOutsideNamespace('Root\Cars');
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

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_1);
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

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_1);
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

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_1);
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

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_7_4);
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['Foo', 'Symfony', 'Doctrine']);
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(0, $violations);
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

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_4);
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

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_4);
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

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_4);
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();
        $violations = new Violations();
        $isReadOnly = new IsReadonly();
        $isReadOnly->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(0, $violations);
    }
}
