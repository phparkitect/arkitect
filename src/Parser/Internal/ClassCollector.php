<?php

declare(strict_types=1);

namespace Arkitect\Parser\Internal;

use Arkitect\Parser\ClassKind;
use Arkitect\Parser\ParsedClass;
use Arkitect\Parser\TypeReference;
use PhpParser\Node;

/**
 * @internal
 */
final class ClassCollector
{
    /**
     * @param list<Node> $stmts
     *
     * @return list<ParsedClass>
     */
    public function collect(array $stmts, string $filePath): array
    {
        return $this->findClasses($stmts, $filePath, $this->collectUseImports($stmts));
    }

    /**
     * Looks for named class-like declarations. Does not, by itself, look at
     * what's inside one — that's a separate walk (collectDependencies),
     * called once per class found, rooted at that class's own body. A
     * top-level function's body is never passed to that walk: there is no
     * code path that could hand it a scope to attach to.
     *
     * @param list<Node>          $nodes
     * @param array<string,string> $imports short name => FQCN, for resolving @throws tags
     *
     * @return list<ParsedClass>
     */
    private function findClasses(array $nodes, string $filePath, array $imports): array
    {
        $classes = [];

        foreach ($nodes as $node) {
            $declaration = $this->declarationOf($node);

            if (null !== $declaration) {
                $ownDocBlock = null !== $node->getDocComment() ? [$node->getDocComment()->getText()] : [];
                $ownAttributes = $this->collectDependencies($declaration['attrGroups']);
                $traits = $this->collectTraits($declaration['stmts']);

                $classes[] = new ParsedClass(
                    fqcn: $declaration['fqcn'],
                    line: $node->getLine(),
                    filePath: $filePath,
                    kind: $declaration['kind'],
                    extends: $declaration['extends'],
                    implements: $declaration['implements'],
                    traits: $traits,
                    dependencies: array_merge(
                        $declaration['extends'],
                        $declaration['implements'],
                        $traits,
                        $ownAttributes,
                        $this->collectDependencies($declaration['stmts']),
                        $this->collectThrowsDependencies($declaration['stmts'], $imports),
                    ),
                    attributes: $ownAttributes,
                    docBlocks: array_merge($ownDocBlock, $this->collectDocBlocks($declaration['stmts'])),
                    isFinal: $declaration['isFinal'],
                    isReadonly: $declaration['isReadonly'],
                    isAbstract: $declaration['isAbstract'],
                );
                $classes = array_merge($classes, $this->findClasses($declaration['stmts'], $filePath, $imports));

                continue;
            }

            $classes = array_merge($classes, $this->findClasses($this->children($node), $filePath, $imports));
        }

        return $classes;
    }

    /**
     * The per-kind differences (Class_/Interface_/Trait_/Enum_) collapsed
     * into one shape, so the rest of the walk doesn't need to know which
     * kind it's looking at. Returns null for anything that isn't a named
     * class-like declaration (including anonymous classes).
     *
     * @return array{fqcn: string, stmts: list<Node>, attrGroups: list<Node\AttributeGroup>, kind: ClassKind, extends: list<TypeReference>, implements: list<TypeReference>, isFinal: bool, isReadonly: bool, isAbstract: bool}|null
     */
    private function declarationOf(Node $node): ?array
    {
        $base = [
            'extends' => [],
            'implements' => [],
            'isFinal' => false,
            'isReadonly' => false,
            'isAbstract' => false,
        ];

        if ($node instanceof Node\Stmt\Class_ && !$node->isAnonymous() && null !== $node->namespacedName) {
            return [
                ...$base,
                'fqcn' => $node->namespacedName->toCodeString(),
                'stmts' => $node->stmts,
                // attribute groups on the declaration itself (`#[X] class Foo`) are a
                // property of $node, not part of $node->stmts — same reason $node's own
                // doc comment needs separate handling from collectDocBlocks($stmts)
                'attrGroups' => $node->attrGroups,
                'kind' => ClassKind::RegularClass,
                'extends' => null !== $node->extends ? [new TypeReference($node->extends->toString(), $node->extends->getLine())] : [],
                'implements' => $this->namesToReferences($node->implements),
                'isFinal' => $node->isFinal(),
                'isReadonly' => $node->isReadonly(),
                'isAbstract' => $node->isAbstract(),
            ];
        }

        if ($node instanceof Node\Stmt\Interface_ && null !== $node->namespacedName) {
            return [
                ...$base,
                'fqcn' => $node->namespacedName->toCodeString(),
                'stmts' => $node->stmts,
                'attrGroups' => $node->attrGroups,
                'kind' => ClassKind::Interface,
                'extends' => $this->namesToReferences($node->extends),
            ];
        }

        if ($node instanceof Node\Stmt\Trait_ && null !== $node->namespacedName) {
            return [
                ...$base,
                'fqcn' => $node->namespacedName->toCodeString(),
                'stmts' => $node->stmts,
                'attrGroups' => $node->attrGroups,
                'kind' => ClassKind::Trait,
            ];
        }

        if ($node instanceof Node\Stmt\Enum_ && null !== $node->namespacedName) {
            return [
                ...$base,
                'fqcn' => $node->namespacedName->toCodeString(),
                'stmts' => $node->stmts,
                'attrGroups' => $node->attrGroups,
                'kind' => ClassKind::Enum,
                'implements' => $this->namesToReferences($node->implements),
            ];
        }

        return null;
    }

    /**
     * @param list<Node\Name> $names
     *
     * @return list<TypeReference>
     */
    private function namesToReferences(array $names): array
    {
        return array_map(
            static fn (Node\Name $name) => new TypeReference($name->toString(), $name->getLine()),
            $names
        );
    }

    /**
     * Every type name referenced anywhere in the given nodes. Called once,
     * with a found class's own body as the root — never with anything a
     * class doesn't own.
     *
     * @param list<Node> $nodes
     *
     * @return list<TypeReference>
     */
    private function collectDependencies(array $nodes): array
    {
        return $this->walk($nodes, fn (Node $node) => array_map(
            static fn (Node\Name\FullyQualified $type) => new TypeReference($type->toString(), $node->getLine()),
            $this->typesReferencedBy($node)
        ));
    }

    /** @return list<Node\Name\FullyQualified> */
    private function typesReferencedBy(Node $node): array
    {
        if ($node instanceof Node\Param || $node instanceof Node\Stmt\Property || $node instanceof Node\Stmt\ClassConst) {
            return $this->unwrapTypes($node->type);
        }

        if ($node instanceof Node\FunctionLike) {
            return $this->unwrapTypes($node->getReturnType());
        }

        if ($node instanceof Node\Stmt\Catch_) {
            return array_filter($node->types, static fn ($t) => $t instanceof Node\Name\FullyQualified);
        }

        if (($node instanceof Node\Expr\New_
            || $node instanceof Node\Expr\StaticCall
            || $node instanceof Node\Expr\ClassConstFetch
            || $node instanceof Node\Expr\Instanceof_)
            && $node->class instanceof Node\Name\FullyQualified) {
            return [$node->class];
        }

        if ($node instanceof Node\Attribute && $node->name instanceof Node\Name\FullyQualified) {
            return [$node->name];
        }

        if ($node instanceof Node\Stmt\Class_ && $node->isAnonymous()) {
            // not a class-like declaration of its own (declarationOf skips
            // it): what it extends/implements are dependencies of whichever
            // class encloses it, which is exactly who's asking here
            $types = array_filter($node->implements, static fn ($n) => $n instanceof Node\Name\FullyQualified);
            if ($node->extends instanceof Node\Name\FullyQualified) {
                $types[] = $node->extends;
            }

            return array_values($types);
        }

        return [];
    }

    /** @return list<Node\Name\FullyQualified> */
    private function unwrapTypes(?Node $type): array
    {
        if ($type instanceof Node\NullableType) {
            return $this->unwrapTypes($type->type);
        }

        if ($type instanceof Node\UnionType || $type instanceof Node\IntersectionType) {
            $types = [];
            foreach ($type->types as $inner) {
                $types = array_merge($types, $this->unwrapTypes($inner));
            }

            return $types;
        }

        if ($type instanceof Node\Name\FullyQualified) {
            return [$type];
        }

        return [];
    }

    /**
     * @param list<Node> $nodes
     *
     * @return list<TypeReference>
     */
    private function collectTraits(array $nodes): array
    {
        return $this->walk(
            $nodes,
            fn (Node $node) => $node instanceof Node\Stmt\TraitUse ? $this->namesToReferences($node->traits) : []
        );
    }

    /**
     * @param list<Node> $nodes
     *
     * @return list<string>
     */
    private function collectDocBlocks(array $nodes): array
    {
        return $this->walk(
            $nodes,
            static fn (Node $node) => null !== $node->getDocComment() ? [$node->getDocComment()->getText()] : []
        );
    }

    /**
     * @throws tags resolved two ways: a leading-`\` name is already fully
     * qualified; a single-segment short name resolves via the file's own
     * `use` imports. Anything else (multi-segment but not fully qualified,
     * or an unimported short name) is left alone: without redoing full
     * namespace resolution there's no reliable way to tell "same-namespace
     * class" from "typo", and guessing wrong is worse than not extracting
     * it at all.
     *
     * @param list<Node>            $nodes
     * @param array<string,string>  $imports short name => FQCN
     *
     * @return list<TypeReference>
     */
    private function collectThrowsDependencies(array $nodes, array $imports): array
    {
        return $this->walk($nodes, fn (Node $node) => $this->throwsTagsOf($node, $imports));
    }

    /**
     * @param array<string,string> $imports short name => FQCN
     *
     * @return list<TypeReference>
     */
    private function throwsTagsOf(Node $node, array $imports): array
    {
        $docComment = $node->getDocComment();

        if (null === $docComment || !preg_match_all('/@throws\s+(\S+)/', $docComment->getText(), $matches)) {
            return [];
        }

        $dependencies = [];

        foreach ($matches[1] as $tag) {
            foreach (explode('|', $tag) as $name) {
                $fqcn = match (true) {
                    str_starts_with($name, '\\') => substr($name, 1),
                    isset($imports[$name]) => $imports[$name],
                    default => null,
                };

                if (null !== $fqcn) {
                    $dependencies[] = new TypeReference($fqcn, $docComment->getStartLine());
                }
            }
        }

        return $dependencies;
    }

    /**
     * @param list<Node> $nodes
     *
     * @return array<string,string> short name => FQCN
     */
    private function collectUseImports(array $nodes): array
    {
        $imports = [];

        foreach ($nodes as $node) {
            if ($node instanceof Node\Stmt\Use_) {
                foreach ($node->uses as $item) {
                    $imports[$item->alias?->toString() ?? $item->name->getLast()] = $item->name->toString();
                }
            }

            if ($node instanceof Node\Stmt\GroupUse) {
                foreach ($node->uses as $item) {
                    $imports[$item->alias?->toString() ?? $item->name->getLast()] = $node->prefix->toString().'\\'.$item->name->toString();
                }
            }

            $imports = [...$imports, ...$this->collectUseImports($this->children($node))];
        }

        return $imports;
    }

    /**
     * The one recursive traversal shape shared by every "find all facts of
     * kind X anywhere in these nodes" query: extract from a node, recurse
     * into its children, flatten. `$extract` never sees a node outside the
     * subtree it was called on — callers control the scope by choosing
     * what to pass as $nodes, not by checking state.
     *
     * @template T
     *
     * @param list<Node>            $nodes
     * @param callable(Node): list<T> $extract
     *
     * @return list<T>
     */
    private function walk(array $nodes, callable $extract): array
    {
        $found = [];

        foreach ($nodes as $node) {
            $found[] = $extract($node);
            $found[] = $this->walk($this->children($node), $extract);
        }

        return array_merge(...$found);
    }

    /** @return list<Node> */
    private function children(Node $node): array
    {
        $children = [];

        foreach ($node->getSubNodeNames() as $name) {
            $value = $node->$name;

            if ($value instanceof Node) {
                $children[] = $value;
            } elseif (\is_array($value)) {
                foreach ($value as $item) {
                    if ($item instanceof Node) {
                        $children[] = $item;
                    }
                }
            }
        }

        return $children;
    }
}
