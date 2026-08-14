<?php

declare(strict_types=1);

namespace Arkitect\Tests\Parser;

use Arkitect\Parser\ClassKind;
use Arkitect\Parser\ParsedClass;
use Arkitect\Parser\Parser;
use Arkitect\Parser\ParseResult;
use Arkitect\Parser\TargetPhpVersion;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    public function test_parses_class_name_and_its_own_declaration_line(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App;

            class Foo
            {
            }
            PHP);

        self::assertSame('App\Foo', $class->fqcn);
        self::assertSame(4, $class->line);
        self::assertSame('test.php', $class->filePath);
    }

    public function test_class_implementing_interface_is_a_dependency(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App;
            use App\Contract;

            class Foo implements Contract
            {
            }
            PHP);

        self::assertSame(['App\Contract'], $this->names($class->implements));
        self::assertSame(['App\Contract'], $this->dependencyNames($class));
    }

    public function test_class_extending_class_is_a_dependency(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App;
            use App\Base;

            class Foo extends Base
            {
            }
            PHP);

        self::assertSame(['App\Base'], $this->names($class->extends));
        self::assertSame(['App\Base'], $this->dependencyNames($class));
    }

    public function test_interface_can_extend_multiple_interfaces(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App;
            use App\A;
            use App\B;

            interface Foo extends A, B
            {
            }
            PHP);

        self::assertSame(ClassKind::Interface, $class->kind);
        self::assertSame(['App\A', 'App\B'], $this->names($class->extends));
    }

    public function test_trait_use_is_a_dependency(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App;
            use App\Loggable;

            class Foo
            {
                use Loggable;
            }
            PHP);

        self::assertSame(['App\Loggable'], $this->names($class->traits));
        self::assertSame(['App\Loggable'], $this->dependencyNames($class));
    }

    public function test_final_readonly_abstract_flags(): void
    {
        $final = $this->onlyClassOf('<?php final class Foo {}');
        self::assertTrue($final->isFinal);

        $readonly = $this->onlyClassOf('<?php readonly class Foo {}');
        self::assertTrue($readonly->isReadonly);

        $abstract = $this->onlyClassOf('<?php abstract class Foo {}');
        self::assertTrue($abstract->isAbstract);

        $plain = $this->onlyClassOf('<?php class Foo {}');
        self::assertFalse($plain->isFinal);
        self::assertFalse($plain->isReadonly);
        self::assertFalse($plain->isAbstract);
    }

    public function test_enum_implementing_interface(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App;
            use App\HasLabel;

            enum Status implements HasLabel
            {
                case Active;
                case Inactive;
            }
            PHP);

        self::assertSame(ClassKind::Enum, $class->kind);
        self::assertSame('App\Status', $class->fqcn);
        self::assertSame(['App\HasLabel'], $this->names($class->implements));
    }

    public function test_typed_property_dependency(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App;
            use App\Infra\Db;

            class Repo
            {
                private Db $db;
            }
            PHP);

        self::assertSame(['App\Infra\Db'], $this->dependencyNames($class));
    }

    public function test_param_type_dependency(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App;
            use App\Infra\Db;

            class Repo
            {
                public function __construct(Db $db) {}
            }
            PHP);

        self::assertSame(['App\Infra\Db'], $this->dependencyNames($class));
    }

    public function test_return_type_dependency(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App;
            use App\Infra\Db;

            class Repo
            {
                public function db(): Db {}
            }
            PHP);

        self::assertSame(['App\Infra\Db'], $this->dependencyNames($class));
    }

    public function test_nullable_type_unwraps_to_its_dependency(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App;
            use App\Infra\Db;

            class Repo
            {
                public function __construct(?Db $db) {}
            }
            PHP);

        self::assertSame(['App\Infra\Db'], $this->dependencyNames($class));
    }

    public function test_union_type_unwraps_to_every_member(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App;
            use App\Infra\Db;
            use App\Infra\Fake;

            class Repo
            {
                public function __construct(Db|Fake $db) {}
            }
            PHP);

        self::assertSame(['App\Infra\Db', 'App\Infra\Fake'], $this->dependencyNames($class));
    }

    public function test_intersection_type_unwraps_to_every_member(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App;
            use App\Countable;
            use App\Iterable_;

            class Repo
            {
                public function __construct(Countable&Iterable_ $collection) {}
            }
            PHP);

        self::assertSame(['App\Countable', 'App\Iterable_'], $this->dependencyNames($class));
    }

    public function test_dnf_type_unwraps_to_every_member(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App;
            use App\A;
            use App\B;
            use App\C;

            class Repo
            {
                public function __construct((A&B)|C $x) {}
            }
            PHP, '8.2');

        self::assertSame(['App\A', 'App\B', 'App\C'], $this->dependencyNames($class));
    }

    public function test_class_const_typed_dependency(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App;
            use App\Infra\Db;

            class Repo
            {
                const Db DEFAULT_DB = null;
            }
            PHP, '8.3');

        self::assertSame(['App\Infra\Db'], $this->dependencyNames($class));
    }

    public function test_catch_dependency(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App;
            use App\Infra\DbException;

            class Repo
            {
                public function save(): void
                {
                    try {
                    } catch (DbException $e) {
                    }
                }
            }
            PHP);

        self::assertSame(['App\Infra\DbException'], $this->dependencyNames($class));
    }

    public function test_multi_catch_dependency(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App;
            use App\Infra\DbException;
            use App\Infra\NetworkException;

            class Repo
            {
                public function save(): void
                {
                    try {
                    } catch (DbException|NetworkException $e) {
                    }
                }
            }
            PHP);

        self::assertSame(['App\Infra\DbException', 'App\Infra\NetworkException'], $this->dependencyNames($class));
    }

    public function test_new_expression_dependency(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App;
            use App\Infra\Db;

            class Repo
            {
                public function save(): void
                {
                    $db = new Db();
                }
            }
            PHP);

        self::assertSame(['App\Infra\Db'], $this->dependencyNames($class));
    }

    public function test_static_call_dependency(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App;
            use App\Infra\Db;

            class Repo
            {
                public function save(): void
                {
                    Db::connect();
                }
            }
            PHP);

        self::assertSame(['App\Infra\Db'], $this->dependencyNames($class));
    }

    public function test_class_const_fetch_dependency(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App;
            use App\Infra\Db;

            class Repo
            {
                public function save(): void
                {
                    $mode = Db::READ_WRITE;
                }
            }
            PHP);

        self::assertSame(['App\Infra\Db'], $this->dependencyNames($class));
    }

    public function test_instanceof_dependency(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App;
            use App\Infra\Db;

            class Repo
            {
                public function save(object $o): void
                {
                    $x = $o instanceof Db;
                }
            }
            PHP);

        self::assertSame(['App\Infra\Db'], $this->dependencyNames($class));
    }

    public function test_attribute_is_a_dependency(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App;
            use App\Infra\AsCommand;

            #[AsCommand]
            class Repo
            {
            }
            PHP);

        self::assertSame(['App\Infra\AsCommand'], $this->names($class->attributes));
        self::assertSame(['App\Infra\AsCommand'], $this->dependencyNames($class));
    }

    public function test_docblock_raw_text_is_captured_without_type_resolution(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App;

            /**
             * @Assert\NotBlank
             */
            class Repo
            {
            }
            PHP);

        self::assertCount(1, $class->docBlocks);
        self::assertStringContainsString('@Assert\NotBlank', $class->docBlocks[0]);
    }

    public function test_anonymous_class_dependencies_attach_to_the_enclosing_class(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App;
            use App\Contract;

            class Factory
            {
                public function make(): object
                {
                    return new class implements Contract {};
                }
            }
            PHP);

        self::assertSame('App\Factory', $class->fqcn);
        self::assertSame(['App\Contract'], $this->dependencyNames($class));
    }

    public function test_multiple_classes_in_one_file_are_kept_separate(): void
    {
        $classes = $this->classesOf(<<<'PHP'
            <?php
            namespace App;
            use App\Infra\Alpha;
            use App\Infra\Beta;

            class First
            {
                public function __construct(Alpha $a) {}
            }

            class Second
            {
                public function __construct(Beta $b) {}
            }
            PHP);

        self::assertCount(2, $classes);
        self::assertSame('App\First', $classes[0]->fqcn);
        self::assertSame(['App\Infra\Alpha'], $this->dependencyNames($classes[0]));
        self::assertSame('App\Second', $classes[1]->fqcn);
        self::assertSame(['App\Infra\Beta'], $this->dependencyNames($classes[1]));
    }

    /**
     * Regression test for the state-leak bug documented in ARCHITECTURE.md:
     * a top-level function's parameter dependency must not attach to the
     * next class the parser encounters.
     */
    public function test_top_level_function_does_not_leak_a_dependency_into_the_next_class(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App;
            use App\Infra\Alpha;

            function helper(Alpha $a): void {}

            class Innocent
            {
            }
            PHP);

        self::assertSame('App\Innocent', $class->fqcn);
        self::assertCount(0, $class->dependencies);
    }

    public function test_php_core_classes_are_not_filtered_out_at_parse_time(): void
    {
        $class = $this->onlyClassOf(<<<'PHP'
            <?php
            namespace App;

            class Event
            {
                public function __construct(\DateTimeImmutable $at) {}
            }
            PHP);

        self::assertSame(['DateTimeImmutable'], $this->dependencyNames($class));
    }

    public function test_syntax_error_produces_a_parsing_error_instead_of_throwing(): void
    {
        $result = $this->parse('<?php class {{{ broken');

        self::assertSame([], $result->classes);
        self::assertNotEmpty($result->errors);
        self::assertSame('test.php', $result->errors[0]->filePath);
    }

    private function parse(string $code, string $targetPhpVersion = '8.4', string $filePath = 'test.php'): ParseResult
    {
        return (new Parser())->parse($code, $filePath, TargetPhpVersion::create($targetPhpVersion));
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
}
