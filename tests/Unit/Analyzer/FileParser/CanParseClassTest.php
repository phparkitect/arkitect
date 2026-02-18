<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer\FileParser;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FileParserFactory;
use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Expression\ForClasses\DependsOnlyOnTheseNamespaces;
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

        $classDescriptions = $this->parseCode($code);

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['Foo']);
        $violations = $this->evaluateRule($dependsOnTheseNamespaces, $classDescriptions[0]);

        self::assertCount(2, $violations);
        self::assertEquals('relativePathName', $violations->get(0)->getFilePath());
        self::assertEquals('relativePathName', $violations->get(1)->getFilePath());
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

        $cd = $this->parseCode($code);

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

        $cd = $this->parseCode($code);

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

        $cd = $this->parseCode($code);

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

        $cd = $this->parseCode($code);

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

        $cd = $this->parseCode($code);
        $cd = $cd[1];

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

        $cd = $this->parseCode($code);
        $cd = $cd[1];

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

        $cd = $this->parseCode($code);

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['Foo', 'Symfony', 'Doctrine']);
        $violations = $this->evaluateRule($dependsOnTheseNamespaces, $cd[0]);

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

        $cd = $this->parseCode($code);

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

        $cd = $this->parseCode($code);

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['Foo', 'Symfony', 'Doctrine']);
        $violations = $this->evaluateRule($dependsOnTheseNamespaces, $cd[0]);

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

        $cd = $this->parseCode($code);

        $notHaveDependencyOutsideNamespace = new NotHaveDependencyOutsideNamespace('Root\Animals');
        $violations = $this->evaluateRule($notHaveDependencyOutsideNamespace, $cd[0]);

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

        $cd = $this->parseCode($code);

        $dependsOnlyOnTheseNamespaces = new DependsOnlyOnTheseNamespaces();
        $violations = $this->evaluateRule($dependsOnlyOnTheseNamespaces, $cd[0]);

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

        $cd = $this->parseCode($code);

        $notHaveDependencyOutsideNamespace = new NotHaveDependencyOutsideNamespace('Root\Cars');
        $violations = $this->evaluateRule($notHaveDependencyOutsideNamespace, $cd[0]);

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

        $cd = $this->parseCode($code, TargetPhpVersion::PHP_8_1);
        $cd = $cd[2]; // class Test

        $deps = array_map(static fn ($d) => $d->getFQCN()->toString(), $cd->getDependencies());
        self::assertContains('Foo\Order', $deps);
        self::assertNotContains('Foo\OrderTwo', $deps);
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

        $cd = $this->parseCode($code, TargetPhpVersion::PHP_8_1);

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['MyProject\AppBundle\Application']);
        $violations = $this->evaluateRule($dependsOnTheseNamespaces, $cd[0]);

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

        $cd = $this->parseCode($code, TargetPhpVersion::PHP_8_1);

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

        $cd = $this->parseCode($code);

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['Foo', 'Symfony', 'Doctrine']);
        $violations = $this->evaluateRule($dependsOnTheseNamespaces, $cd[0]);

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

        $cd = $this->parseCode($code, TargetPhpVersion::PHP_8_4);
        $isFinal = new IsFinal();
        $violations = $this->evaluateRule($isFinal, $cd[0]);

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

        $cd = $this->parseCode($code, TargetPhpVersion::PHP_8_4);
        $isAbstract = new IsAbstract();
        $violations = $this->evaluateRule($isAbstract, $cd[0]);

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

        $cd = $this->parseCode($code, TargetPhpVersion::PHP_8_4);
        $isReadOnly = new IsReadonly();
        $violations = $this->evaluateRule($isReadOnly, $cd[0]);

        self::assertCount(0, $violations);
    }

    private function parseCode(string $code, ?string $version = null): array
    {
        $fp = FileParserFactory::forPhpVersion($version ?? TargetPhpVersion::PHP_8_0);
        $fp->parse($code, 'relativePathName');

        return $fp->getClassDescriptions();
    }

    private function evaluateRule($rule, ClassDescription $classDescription): Violations
    {
        $violations = new Violations();
        $rule->evaluate($classDescription, $violations, 'we want to add this rule for our software');

        return $violations;
    }
}
