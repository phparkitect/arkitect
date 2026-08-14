<?php

declare(strict_types=1);

namespace Arkitect\Tests\Parser;

use Arkitect\Parser\ClassKind;
use Arkitect\Parser\Parser;
use Arkitect\Parser\TargetPhpVersion;
use PHPUnit\Framework\TestCase;

final class CollectTest extends TestCase
{
    public function test_a_file_with_no_class_produces_no_classes(): void
    {
        $result = (new Parser())->parse('<?php $x = 1;', 'test.php', TargetPhpVersion::create('8.5'));

        self::assertSame([], $result->classes);
    }

    public function test_a_single_class_is_collected_by_its_name(): void
    {
        $result = (new Parser())->parse('<?php namespace App; class Foo {}', 'test.php', TargetPhpVersion::create('8.5'));

        self::assertCount(1, $result->classes);
        self::assertSame('App\Foo', $result->classes[0]->fqcn);
    }

    public function test_a_constructor_param_type_is_a_dependency_of_its_class(): void
    {
        $result = (new Parser())->parse(<<<'PHP'
            <?php
            namespace App;
            use App\Infra\Db;

            class Repo
            {
                public function __construct(Db $db) {}
            }
            PHP, 'test.php', TargetPhpVersion::create('8.5'));

        self::assertCount(1, $result->classes);
        self::assertSame(['App\Infra\Db'], array_map(static fn ($d) => $d->name, $result->classes[0]->dependencies));
    }

    public function test_a_top_level_function_parameter_does_not_leak_into_the_next_class(): void
    {
        $result = (new Parser())->parse(<<<'PHP'
            <?php
            namespace App;
            use App\Infra\Alpha;

            function helper(Alpha $a): void {}

            class Innocent
            {
            }
            PHP, 'test.php', TargetPhpVersion::create('8.5'));

        self::assertCount(1, $result->classes);
        self::assertSame('App\Innocent', $result->classes[0]->fqcn);
        self::assertSame([], $result->classes[0]->dependencies);
    }

    public function test_extends_and_implements_are_dependencies(): void
    {
        $result = (new Parser())->parse(<<<'PHP'
            <?php
            namespace App;
            use App\Base;
            use App\Contract;

            class Foo extends Base implements Contract
            {
            }
            PHP, 'test.php', TargetPhpVersion::create('8.5'));

        $class = $result->classes[0];
        self::assertSame(['App\Base'], array_map(static fn ($t) => $t->name, $class->extends));
        self::assertSame(['App\Contract'], array_map(static fn ($t) => $t->name, $class->implements));
        self::assertSame(['App\Base', 'App\Contract'], array_map(static fn ($t) => $t->name, $class->dependencies));
    }

    public function test_trait_use_is_a_dependency(): void
    {
        $result = (new Parser())->parse(<<<'PHP'
            <?php
            namespace App;
            use App\Loggable;

            class Foo
            {
                use Loggable;
            }
            PHP, 'test.php', TargetPhpVersion::create('8.5'));

        $class = $result->classes[0];
        self::assertSame(['App\Loggable'], array_map(static fn ($t) => $t->name, $class->traits));
        self::assertSame(['App\Loggable'], array_map(static fn ($t) => $t->name, $class->dependencies));
    }

    public function test_interface_enum_and_trait_are_collected_and_flagged(): void
    {
        $result = (new Parser())->parse(<<<'PHP'
            <?php
            namespace App;
            use App\A;
            use App\HasLabel;

            interface Iface extends A {}
            trait Trt {}
            enum Status implements HasLabel { case Active; }
            PHP, 'test.php', TargetPhpVersion::create('8.5'));

        self::assertCount(3, $result->classes);

        $iface = $result->classes[0];
        self::assertSame('App\Iface', $iface->fqcn);
        self::assertSame(ClassKind::Interface, $iface->kind);
        self::assertSame(['App\A'], array_map(static fn ($t) => $t->name, $iface->extends));

        $trait = $result->classes[1];
        self::assertSame(ClassKind::Trait, $trait->kind);

        $enum = $result->classes[2];
        self::assertSame(ClassKind::Enum, $enum->kind);
        self::assertSame(['App\HasLabel'], array_map(static fn ($t) => $t->name, $enum->implements));
    }

    public function test_anonymous_class_dependencies_attach_to_the_enclosing_class(): void
    {
        $result = (new Parser())->parse(<<<'PHP'
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
            PHP, 'test.php', TargetPhpVersion::create('8.5'));

        self::assertCount(1, $result->classes);
        self::assertSame('App\Factory', $result->classes[0]->fqcn);
        self::assertSame(['App\Contract'], array_map(static fn ($t) => $t->name, $result->classes[0]->dependencies));
    }

    public function test_use_function_and_use_const_are_not_dependencies(): void
    {
        $result = (new Parser())->parse(<<<'PHP'
            <?php
            namespace App;
            use function array_map;
            use const App\Infra\SOME_CONST;
            use App\Infra\Db;

            class Repo
            {
                public function __construct(Db $db) {}
            }
            PHP, 'test.php', TargetPhpVersion::create('8.5'));

        self::assertSame(['App\Infra\Db'], array_map(static fn ($d) => $d->name, $result->classes[0]->dependencies));
    }

    public function test_property_hook_does_not_prevent_the_property_type_from_being_a_dependency(): void
    {
        $result = (new Parser())->parse(<<<'PHP'
            <?php
            namespace App;
            use App\Infra\Money;

            class Product
            {
                public Money $price {
                    get => $this->price;
                    set(Money $value) {
                        $this->price = $value;
                    }
                }
            }
            PHP, 'test.php', TargetPhpVersion::create('8.5'));

        self::assertCount(1, $result->classes);
        // two independent references: the property's own type, and the
        // set-hook's parameter type — not deduplicated, same as any other
        // type referenced twice in a class (e.g. two constructor params of
        // the same type also produce two entries)
        self::assertSame(
            ['App\Infra\Money', 'App\Infra\Money'],
            array_map(static fn ($d) => $d->name, $result->classes[0]->dependencies)
        );
    }

    public function test_at_throws_with_a_fully_qualified_name_is_a_dependency(): void
    {
        $result = (new Parser())->parse(<<<'PHP'
            <?php
            namespace App;

            class Repo
            {
                /**
                 * @throws \App\Infra\DbException
                 */
                public function save(): void {}
            }
            PHP, 'test.php', TargetPhpVersion::create('8.5'));

        self::assertSame(['App\Infra\DbException'], array_map(static fn ($d) => $d->name, $result->classes[0]->dependencies));
    }

    public function test_at_throws_with_a_short_name_resolves_via_the_files_use_import(): void
    {
        $result = (new Parser())->parse(<<<'PHP'
            <?php
            namespace App;
            use App\Infra\DbException;

            class Repo
            {
                /**
                 * @throws DbException
                 */
                public function save(): void {}
            }
            PHP, 'test.php', TargetPhpVersion::create('8.5'));

        self::assertSame(['App\Infra\DbException'], array_map(static fn ($d) => $d->name, $result->classes[0]->dependencies));
    }

    public function test_at_throws_with_an_unimported_short_name_is_not_guessed(): void
    {
        $result = (new Parser())->parse(<<<'PHP'
            <?php
            namespace App;

            class Repo
            {
                /**
                 * @throws DbException
                 */
                public function save(): void {}
            }
            PHP, 'test.php', TargetPhpVersion::create('8.5'));

        self::assertSame([], $result->classes[0]->dependencies);
    }

    public function test_at_throws_with_a_union_resolves_each_member(): void
    {
        $result = (new Parser())->parse(<<<'PHP'
            <?php
            namespace App;
            use App\Infra\DbException;
            use App\Infra\NetworkException;

            class Repo
            {
                /**
                 * @throws DbException|NetworkException
                 */
                public function save(): void {}
            }
            PHP, 'test.php', TargetPhpVersion::create('8.5'));

        self::assertSame(
            ['App\Infra\DbException', 'App\Infra\NetworkException'],
            array_map(static fn ($d) => $d->name, $result->classes[0]->dependencies)
        );
    }

    public function test_a_syntax_error_produces_a_parsing_error_instead_of_throwing(): void
    {
        $result = (new Parser())->parse('<?php class {{{ broken', 'test.php', TargetPhpVersion::create('8.5'));

        self::assertSame([], $result->classes);
        self::assertNotEmpty($result->errors);
        self::assertSame('test.php', $result->errors[0]->filePath);
    }

    public function test_docblock_raw_text_is_captured(): void
    {
        $result = (new Parser())->parse(<<<'PHP'
            <?php
            namespace App;

            /**
             * @Assert\NotBlank
             */
            class Repo
            {
            }
            PHP, 'test.php', TargetPhpVersion::create('8.5'));

        self::assertCount(1, $result->classes[0]->docBlocks);
        self::assertStringContainsString('@Assert\NotBlank', $result->classes[0]->docBlocks[0]);
    }
}
