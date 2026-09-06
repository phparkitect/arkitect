<?php

declare(strict_types=1);

namespace Arkitect\Tests\Parser;

use Arkitect\Parser\ClassKind;
use Arkitect\Parser\ClassParser;
use Arkitect\Parser\ParsedClass;
use Arkitect\Parser\ParseResult;
use Arkitect\Parser\TargetPhpVersion;
use PHPUnit\Framework\TestCase;

/**
 * v1's parser test suite, ported onto the v2 API with v1's expectations
 * kept as they were. A failure here is a behaviour v1 guarantees and v2
 * does not — a regression to fix or a divergence to accept on purpose.
 * Where one has been accepted, the test asserts v2's answer instead and
 * says what v1 answers and why it stopped being the right question.
 */
final class EdgeCasesFromV1Test extends TestCase
{
    public function test_instanceof_names_a_dependency_but_a_variable_and_self_do_not(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
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
            PHP);

        self::assertSame(['Foo\Bar\MyClass'], $this->dependencyNames($class));
    }

    public function test_an_unreferenced_use_import_is_not_a_dependency(): void
    {
        $classes = $this->classesOf(<<<'PHP'
            <?php

            namespace Root\Namespace1;

            use Root\Namespace2\D;

            class Dog implements AnInterface, InterfaceTwo
            {
            }

            class Cat implements AnInterface
            {
            }
            PHP);

        self::assertCount(2, $classes);
        self::assertSame(
            ['Root\Namespace1\AnInterface:7', 'Root\Namespace1\InterfaceTwo:7'],
            $this->dependencies($classes[0])
        );
    }

    public function test_dependencies_inside_an_anonymous_class_belong_to_the_enclosing_class(): void
    {
        $classes = $this->classesOf(<<<'PHP'
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
            PHP);

        self::assertCount(2, $classes);
        self::assertSame([
            'Root\Namespace1\AnInterface:7',
            'Root\Namespace1\InterfaceTwo:7',
            'Root\Namespace1\Another\ForbiddenInterface:11',
            'Root\Namespace1\Proj:23',
        ], $this->dependencies($classes[0]));
    }

    public function test_an_anonymous_class_extends_is_a_dependency_of_the_enclosing_class(): void
    {
        $classes = $this->classesOf(<<<'PHP'
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
            PHP);

        self::assertSame([
            'Root\Namespace1\AnInterface:7',
            'Root\Namespace1\InterfaceTwo:7',
            'Root\Namespace1\Another\ForbiddenExtend:11',
        ], $this->dependencies($classes[0]));
    }

    public function test_an_anonymous_class_extends_does_not_become_the_enclosing_extends(): void
    {
        $classes = $this->classesOf(<<<'PHP'
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
            PHP);

        self::assertSame(['Root\Animals\Animal'], $this->names($classes[1]->extends));
    }

    public function test_every_dependency_of_a_class_with_the_line_it_is_written_on(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
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
            PHP);

        self::assertSame([
            'Foo\Baz\Baz:10',
            'Symfony\Component\HttpFoundation\Request:12',
            'Foo\Baz\Nullable:12',
            'Doctrine\MongoDB\Collection:14',
            'Foo\Baz\StaticClass:15',
        ], $this->dependencies($class));
    }

    public function test_self_static_and_parent_are_not_dependencies(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
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

                public function doSomething(self $self, $static)
                {
                }
            }
            PHP);

        self::assertSame(['Root\Animals\Animal'], $this->dependencyNames($class));
    }

    public function test_a_class_constant_fetch_on_an_imported_class_is_a_dependency(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
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
            PHP);

        self::assertSame(['Root\Cars\AbstractCar', 'AnotherNamespace\CarMake'], $this->dependencyNames($class));
    }

    public function test_an_arrow_function_body_adds_no_dependency(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
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
            PHP);

        self::assertSame([], $this->dependencyNames($class));
    }

    public function test_a_return_type_inside_an_interface_is_a_dependency(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php

            namespace MyProject\AppBundle\Application;

            use Doctrine\ORM\QueryBuilder;

            interface BookRepositoryInterface
            {
                public function getBookList(): QueryBuilder;
            }
            PHP);

        self::assertSame(ClassKind::Interface, $class->kind);
        self::assertSame(['Doctrine\ORM\QueryBuilder'], $this->dependencyNames($class));
    }

    public function test_a_return_type_inside_a_trait_is_a_dependency(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php

            namespace MyProject\AppBundle\Application;

            use Doctrine\ORM\QueryBuilder;

            trait BookRepositoryInterface
            {
                public function getBookList(): QueryBuilder
                {
                }
            }
            PHP);

        self::assertSame(ClassKind::Trait, $class->kind);
        self::assertSame(['Doctrine\ORM\QueryBuilder'], $this->dependencyNames($class));
    }

    public function test_a_union_type_is_unwrapped_in_both_positions(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace Foo\Bar;

            use Symfony\Component\HttpFoundation\Request;
            use Symfony\Component\HttpFoundation\Response;

            class MyClass
            {
                public function handle(Request|Response $message): Request|Response|null
                {
                    return $message;
                }
            }
            PHP);

        self::assertSame([
            'Symfony\Component\HttpFoundation\Request',
            'Symfony\Component\HttpFoundation\Response',
            'Symfony\Component\HttpFoundation\Request',
            'Symfony\Component\HttpFoundation\Response',
        ], $this->dependencyNames($class));
    }

    public function test_a_closure_return_type_is_a_dependency(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace Foo\Bar;

            use Symfony\Component\HttpFoundation\Request;

            class MyClass
            {
                public function make(): callable
                {
                    return function (): ?Request {
                        return null;
                    };
                }
            }
            PHP);

        self::assertSame(['Symfony\Component\HttpFoundation\Request'], $this->dependencyNames($class));
    }

    public function test_an_arrow_function_return_type_is_a_dependency(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace Foo\Bar;

            use Symfony\Component\HttpFoundation\Request;
            use Symfony\Component\HttpFoundation\Response;

            class MyClass
            {
                public function make(): callable
                {
                    return fn (): Request|Response => new Request();
                }
            }
            PHP);

        self::assertSame([
            'Symfony\Component\HttpFoundation\Request',
            'Symfony\Component\HttpFoundation\Response',
            'Symfony\Component\HttpFoundation\Request',
        ], $this->dependencyNames($class));
    }

    public function test_a_typed_class_constant_is_a_dependency_and_a_scalar_one_is_not(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace Foo\Bar;

            use Symfony\Component\HttpFoundation\Request;
            use Symfony\Component\HttpFoundation\Response;

            class MyClass
            {
                public const ?Request EMPTY_REQUEST = null;

                public const Request|Response|null FALLBACK = null;

                public const string NAME = 'name';
            }
            PHP, '8.3');

        self::assertSame([
            'Symfony\Component\HttpFoundation\Request',
            'Symfony\Component\HttpFoundation\Request',
            'Symfony\Component\HttpFoundation\Response',
        ], $this->dependencyNames($class));
    }

    public function test_scalar_and_array_types_are_not_dependencies(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php

            namespace App\Domain;

            class MyClass
            {
                private array $field1;
                public function __construct(array $field1, int $field2, self $field3)
                {
                    $this->field1 = $field1;
                }
            }
            PHP);

        self::assertSame([], $this->dependencyNames($class));
    }

    public function test_a_nullable_scalar_parameter_is_not_a_dependency(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
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
            PHP);

        self::assertSame([], $this->dependencyNames($class));
    }

    public function test_an_anonymous_class_does_not_change_the_enclosing_final_flag(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App\Foo;

            final class User {
                public function __construct() {
                   $class = new class() extends Bundle {};
                }
            }
            PHP);

        self::assertTrue($class->isFinal);
    }

    public function test_an_anonymous_class_does_not_change_the_enclosing_abstract_flag(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App\Foo;

            abstract class User {
                public function bar() {
                    $class = new class() extends Bundle {};
                }

                abstract public function foo();
            }
            PHP);

        self::assertTrue($class->isAbstract);
    }

    public function test_an_anonymous_class_does_not_change_the_enclosing_readonly_flag(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App\Foo;

             readonly class User {
                public function __construct() {
                   $class = new class() extends Bundle {};
                }
            }
            PHP);

        self::assertTrue($class->isReadonly);
    }

    public function test_attributes_of_a_class_in_the_global_namespace(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php

            use Bar\FooAttr;

            #[FooAttr('bar')]
            #[Baz]
            class Foo {}
            PHP);

        self::assertSame(['Bar\FooAttr', 'Baz'], $this->names($class->attributes));
    }

    /**
     * v1 answers both, and `HaveAttribute('Baz')` is true of this trait as
     * a result. That was never asked for: #263 introduced the rule when
     * only a declaration's own attributes were collected, and #444/#461
     * widened collection to close a *dependency* gap — `addAttribute()`
     * files what it sees under both lists, so the rule widened with it.
     * PHP keeps the two apart as well: `ReflectionClass::getAttributes()`
     * does not return a method's.
     */
    public function test_an_attribute_on_a_member_is_a_dependency_and_not_the_declarations_own(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php

            namespace Root\Cars;

            use Bar\FooAttr;

            #[FooAttr('bar')]
            trait ATrait
            {
                #[Baz]
                public function foo(): string { return 'foo'; }
            }
            PHP);

        self::assertSame(['Bar\FooAttr'], $this->names($class->attributes));
        self::assertContains('Root\Cars\Baz', $this->dependencyNames($class));
    }

    public function test_attributes_of_an_enum(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php

            namespace Root\Cars;

            use Bar\FooAttr;

            #[FooAttr('bar')]
            #[Baz]
            enum Enum
            {
                case Hearts;
                case Diamonds;
            }
            PHP);

        self::assertSame(['Bar\FooAttr', 'Root\Cars\Baz'], $this->names($class->attributes));
    }

    public function test_an_attribute_on_an_interface_method_is_read_the_same_way(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php

            namespace Root\Cars;

            use Bar\FooAttr;

            #[FooAttr('bar')]
            interface AnInterface
            {
                #[Baz]
                public function foo(): string;
            }
            PHP);

        self::assertSame(['Bar\FooAttr'], $this->names($class->attributes));
        self::assertContains('Root\Cars\Baz', $this->dependencyNames($class));
    }

    /** @dataProvider provide_enums */
    public function test_every_shape_of_enum_is_recognised_as_one(string $code): void
    {
        foreach ($this->classesOf($code) as $class) {
            self::assertSame(ClassKind::Enum, $class->kind);
        }
    }

    public static function provide_enums(): \Generator
    {
        yield 'default enum' => [
            <<<'PHP'
                <?php
                namespace App\Foo;

                enum DefaultEnum
                {
                    case FOO;
                }
                PHP,
        ];

        yield 'string enum' => [
            <<<'PHP'
                <?php
                namespace App\Foo;

                enum StringEnum: string
                {
                    case BAR = 'bar';
                }
                PHP,
        ];

        yield 'integer enum' => [
            <<<'PHP'
                <?php
                namespace App\Foo;

                enum IntEnum: int
                {
                    case BAZ = 42;
                }
                PHP,
        ];

        yield 'the same enum declared twice' => [
            <<<'PHP'
                <?php
                namespace App\Foo;

                enum DefaultEnum
                {
                    case FOO;
                }

                enum IntEnum: int
                {
                    case BAZ = 42;
                }

                enum IntEnum: int
                {
                    case BAZ = 42;
                }
                PHP,
        ];
    }

    public function test_dependencies_written_inside_property_hooks(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App\Foo;

            use App\Services\Formatter;
            use App\Services\Validator;
            use App\Services\Logger;

            class User {
                public string $name {
                    get {
                        $formatter = new Formatter();
                        return $formatter->format($this->name);
                    }
                    set {
                        $validator = new Validator();
                        $validator->validate($value);
                        $this->name = $value;
                        Logger::log('Name set');
                    }
                }
            }
            PHP);

        $names = $this->dependencyNames($class);

        self::assertContains('App\Services\Formatter', $names);
        self::assertContains('App\Services\Validator', $names);
        self::assertContains('App\Services\Logger', $names);
    }

    public function test_a_property_hook_parameter_type_is_a_dependency(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App\Foo;

            use App\ValueObjects\Name;

            class User {
                public string $name {
                    set (Name $name) {
                        $this->name = $name->toString();
                    }
                }
            }
            PHP);

        self::assertSame(['App\ValueObjects\Name'], $this->dependencyNames($class));
    }

    public function test_a_file_that_is_not_php_is_neither_a_class_nor_an_error(): void
    {
        $result = $this->parse('');

        self::assertSame([], $result->classes);
        self::assertSame([], $result->errors);
    }

    public function test_a_file_with_nothing_but_an_opening_tag_is_neither(): void
    {
        $result = $this->parse('<?php');

        self::assertSame([], $result->classes);
        self::assertSame([], $result->errors);
    }

    public function test_a_recoverable_syntax_error_still_yields_the_class_it_could_read(): void
    {
        $result = $this->parse(<<<'PHP'
            <?php

            namespace Root\Animals;

            class Animal
            {
                public function __construct()
                {
                FOO
                }
            }
            PHP);

        self::assertCount(1, $result->classes);
        self::assertSame('Root\Animals\Animal', $result->classes[0]->fqcn);
        self::assertCount(1, $result->errors);
    }

    public function test_a_var_tag_naming_an_imported_class_is_a_dependency(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php

            namespace MyProject\AppBundle\Application;

            use Symfony\Component\Validator\Constraints\NotBlank;

            class ApplicationLevelDto
            {
                /**
                * @var NotBlank
                */
                public $foo;
            }
            PHP);

        self::assertSame(
            ['Symfony\Component\Validator\Constraints\NotBlank'],
            $this->dependencyNames($class)
        );
    }

    /** @dataProvider provide_typed_array_docblocks */
    public function test_a_typed_array_docblock_names_a_dependency(string $code): void
    {
        self::assertContains('Application\MyDto', $this->dependencyNames($this->onlyClassOf($code)));
    }

    public static function provide_typed_array_docblocks(): \Generator
    {
        $class = static fn (string $member): string => <<<PHP
            <?php
            namespace Domain\Foo;

            use Application\MyDto;

            class MyClass
            {
            $member
            }
            PHP;

        yield 'property, generics syntax' => [$class("    /** @var array<int, MyDto> */\n    private array \$dtoList;")];
        yield 'property, list syntax' => [$class("    /** @var list<MyDto> */\n    private array \$dtoList;")];
        yield 'property, legacy syntax' => [$class("    /** @var MyDto[] */\n    private array \$dtoList;")];
        yield 'param, generics syntax' => [$class("    /** @param array<int, MyDto> \$l */\n    public function __construct(array \$l) {}")];
        yield 'param, list syntax' => [$class("    /** @param list<MyDto> \$l */\n    public function __construct(array \$l) {}")];
        yield 'param, legacy syntax' => [$class("    /** @param MyDto[] \$l */\n    public function __construct(array \$l) {}")];
        yield 'return, generics syntax' => [$class("    /** @return array<int, MyDto> */\n    public function getList(): array { return []; }")];
        yield 'return, list syntax' => [$class("    /** @return list<MyDto> */\n    public function getList(): array { return []; }")];
        yield 'return, legacy syntax' => [$class("    /** @return MyDto[] */\n    public function getList(): array { return []; }")];
    }

    public function test_a_custom_annotation_resolves_through_an_aliased_import(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php

            namespace MyProject\AppBundle\Application;

            use Symfony\Component\Validator\Constraints as Assert;

            class ApplicationLevelDto
            {
                /**
                 * @Assert\NotBlank
                 */
                public string|null $foo;
            }
            PHP);

        self::assertSame(
            ['Symfony\Component\Validator\Constraints\NotBlank'],
            $this->dependencyNames($class)
        );
    }

    public function test_a_throws_tag_with_an_unimported_short_name_resolves_in_the_files_own_namespace(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php

            namespace App\Services;

            class MyService
            {
                /**
                 * @throws \Exception
                 * @throws \Domain\FooException
                 * @throws BarException
                 */
                public function doSomething()
                {
                }
            }
            PHP);

        $names = $this->dependencyNames($class);

        self::assertContains('Domain\FooException', $names);
        self::assertContains('App\Services\BarException', $names);
    }

    private function parse(string $code, string $targetPhpVersion = '8.4'): ParseResult
    {
        return (new ClassParser())->parse($code, 'relativePathName', TargetPhpVersion::create($targetPhpVersion));
    }

    /** @return list<ParsedClass> */
    private function classesOf(string $code, string $targetPhpVersion = '8.4'): array
    {
        return $this->parse($code, $targetPhpVersion)->classes;
    }

    private function onlyClassOf(string $code, string $targetPhpVersion = '8.4'): ParsedClass
    {
        $classes = $this->classesOf($code, $targetPhpVersion);
        self::assertCount(1, $classes, 'expected exactly one parsed class');

        return $classes[0];
    }

    /** @return list<string> */
    private function names(iterable $refs): array
    {
        return array_map(static fn ($t) => $t->name, [...$refs]);
    }

    /** @return list<string> */
    private function dependencyNames(ParsedClass $class): array
    {
        return $this->names($class->dependencies);
    }

    /** @return list<string> name:line, the shape v1 asserted on */
    private function dependencies(ParsedClass $class): array
    {
        return array_map(static fn ($t) => $t->name.':'.$t->line, [...$class->dependencies]);
    }
}
