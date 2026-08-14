<?php

declare(strict_types=1);

namespace Arkitect\Tests\Resolve;

use Arkitect\Parser\ClassKind;
use Arkitect\Parser\ParsedClass;
use Arkitect\Parser\TypeReference;
use Arkitect\Parser\TypeReferences;
use Arkitect\Resolve\ClassGraph;
use Arkitect\Resolve\Membership;
use PHPUnit\Framework\TestCase;

final class ClassGraphTest extends TestCase
{
    public function test_a_class_is_a_itself_even_if_never_parsed(): void
    {
        $classGraph = new ClassGraph();

        self::assertSame(Membership::Yes, $classGraph->isA('App\Foo', 'App\Foo'));
    }

    public function test_direct_extends_is_a_match(): void
    {
        $classGraph = new ClassGraph($this->classOf('App\Child', extends: ['App\Parent_']));

        self::assertSame(Membership::Yes, $classGraph->isA('App\Child', 'App\Parent_'));
    }

    public function test_transitive_extends_is_a_match(): void
    {
        $classGraph = new ClassGraph(
            $this->classOf('App\A', extends: ['App\B']),
            $this->classOf('App\B', extends: ['App\C']),
        );

        self::assertSame(Membership::Yes, $classGraph->isA('App\A', 'App\C'));
    }

    public function test_direct_implements_is_a_match(): void
    {
        $classGraph = new ClassGraph($this->classOf('App\Impl', implements: ['App\Iface']));

        self::assertSame(Membership::Yes, $classGraph->isA('App\Impl', 'App\Iface'));
    }

    public function test_interface_extending_interface_is_transitive(): void
    {
        $classGraph = new ClassGraph(
            $this->classOf('App\Impl', implements: ['App\B']),
            $this->classOf('App\B', extends: ['App\A']),
        );

        self::assertSame(Membership::Yes, $classGraph->isA('App\Impl', 'App\A'));
    }

    public function test_a_class_is_a_the_interfaces_its_parent_implements(): void
    {
        $classGraph = new ClassGraph(
            $this->classOf('App\Child', extends: ['App\Parent_']),
            $this->classOf('App\Parent_', implements: ['App\Iface']),
        );

        self::assertSame(Membership::Yes, $classGraph->isA('App\Child', 'App\Iface'));
    }

    public function test_unrelated_classes_are_not_a_match(): void
    {
        $classGraph = new ClassGraph(
            $this->classOf('App\A', extends: ['App\B']),
            $this->classOf('App\B'),
        );

        self::assertSame(Membership::No, $classGraph->isA('App\A', 'App\Unrelated'));
    }

    public function test_an_ancestor_outside_the_parsed_set_is_unknown_not_false(): void
    {
        $classGraph = new ClassGraph($this->classOf('App\Child', extends: ['Vendor\Unparsed']));

        self::assertSame(Membership::Unknown, $classGraph->isA('App\Child', 'App\Something'));
    }

    public function test_a_confirmed_match_on_one_branch_wins_over_an_unknown_branch(): void
    {
        $classGraph = new ClassGraph(
            $this->classOf('App\Child', extends: ['Vendor\Unparsed'], implements: ['App\Iface']),
        );

        self::assertSame(Membership::Yes, $classGraph->isA('App\Child', 'App\Iface'));
    }

    public function test_using_a_trait_does_not_make_it_a_match(): void
    {
        $classGraph = new ClassGraph($this->classOf('App\Foo', traits: ['App\Loggable']));

        self::assertSame(Membership::No, $classGraph->isA('App\Foo', 'App\Loggable'));
    }

    public function test_a_class_never_seen_anywhere_is_unknown(): void
    {
        $classGraph = new ClassGraph($this->classOf('App\Foo'));

        self::assertSame(Membership::Unknown, $classGraph->isA('Totally\Unseen', 'App\Foo'));
    }

    private function classOf(string $fqcn, array $extends = [], array $implements = [], array $traits = []): ParsedClass
    {
        return new ParsedClass(
            fqcn: $fqcn,
            line: 1,
            filePath: 'test.php',
            kind: ClassKind::RegularClass,
            extends: new TypeReferences(...array_map(static fn ($n) => new TypeReference($n, 1), $extends)),
            implements: new TypeReferences(...array_map(static fn ($n) => new TypeReference($n, 1), $implements)),
            traits: new TypeReferences(...array_map(static fn ($n) => new TypeReference($n, 1), $traits)),
            dependencies: new TypeReferences(),
            attributes: new TypeReferences(),
            docBlocks: [],
            isFinal: false,
            isReadonly: false,
            isAbstract: false,
        );
    }
}
