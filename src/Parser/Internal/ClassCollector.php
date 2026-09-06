<?php

declare(strict_types=1);

namespace Arkitect\Parser\Internal;

use Arkitect\Parser\ClassKind;
use Arkitect\Parser\ParsedClass;
use Arkitect\Parser\TypeReference;
use Arkitect\Parser\TypeReferences;
use PhpParser\Node;

/**
 * @internal
 */
final class ClassCollector
{
    /** The docblock tags whose text opens with a type expression. */
    private const TYPE_TAGS = ['var', 'param', 'return', 'throws'];

    private const NAME_SEGMENT = '[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*';

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
     * @param array<string,string> $imports short name => FQCN, for resolving docblock names
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
                $ownAttributes = $this->collectDependencies($declaration->attrGroups);
                $traits = $this->collectTraits($declaration->stmts);

                $classes[] = new ParsedClass(
                    fqcn: $declaration->fqcn,
                    line: $node->getLine(),
                    filePath: $filePath,
                    kind: $declaration->kind,
                    extends: new TypeReferences(...$declaration->extends),
                    implements: new TypeReferences(...$declaration->implements),
                    traits: new TypeReferences(...$traits),
                    dependencies: new TypeReferences(
                        ...$declaration->extends,
                        ...$declaration->implements,
                        ...$traits,
                        ...$ownAttributes,
                        ...$this->collectDependencies($declaration->stmts),
                        ...$this->collectDocBlockDependencies($declaration->stmts, $imports),
                    ),
                    attributes: new TypeReferences(...$ownAttributes),
                    docBlocks: array_merge($ownDocBlock, $this->collectDocBlocks($declaration->stmts)),
                    isFinal: $declaration->isFinal,
                    isReadonly: $declaration->isReadonly,
                    isAbstract: $declaration->isAbstract,
                );
                $classes = array_merge($classes, $this->findClasses($declaration->stmts, $filePath, $imports));

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
     */
    private function declarationOf(Node $node): ?Declaration
    {
        if ($node instanceof Node\Stmt\Class_ && !$node->isAnonymous() && null !== $node->namespacedName) {
            return new Declaration(
                fqcn: $node->namespacedName->toCodeString(),
                stmts: $node->stmts,
                // attribute groups on the declaration itself (`#[X] class Foo`) are a
                // property of $node, not part of $node->stmts — same reason $node's own
                // doc comment needs separate handling from collectDocBlocks($stmts)
                attrGroups: $node->attrGroups,
                kind: ClassKind::RegularClass,
                extends: null !== $node->extends ? [new TypeReference($node->extends->toString(), $node->extends->getLine())] : [],
                implements: $this->namesToReferences($node->implements),
                isFinal: $node->isFinal(),
                isReadonly: $node->isReadonly(),
                isAbstract: $node->isAbstract(),
            );
        }

        if ($node instanceof Node\Stmt\Interface_ && null !== $node->namespacedName) {
            return new Declaration(
                fqcn: $node->namespacedName->toCodeString(),
                stmts: $node->stmts,
                attrGroups: $node->attrGroups,
                kind: ClassKind::Interface,
                extends: $this->namesToReferences($node->extends),
            );
        }

        if ($node instanceof Node\Stmt\Trait_ && null !== $node->namespacedName) {
            return new Declaration(
                fqcn: $node->namespacedName->toCodeString(),
                stmts: $node->stmts,
                attrGroups: $node->attrGroups,
                kind: ClassKind::Trait,
            );
        }

        if ($node instanceof Node\Stmt\Enum_ && null !== $node->namespacedName) {
            return new Declaration(
                fqcn: $node->namespacedName->toCodeString(),
                stmts: $node->stmts,
                attrGroups: $node->attrGroups,
                kind: ClassKind::Enum,
                implements: $this->namesToReferences($node->implements),
                // the keyword can't be written on an enum, but an enum is
                // final: recording false would describe the syntax while
                // misstating the type
                isFinal: true,
            );
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
     * Every type name referenced anywhere in the given nodes.
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
     * Type names written in docblocks: the type expression of `@var`,
     * `@param`, `@return` and `@throws`, and the name of a Doctrine-style
     * annotation, where `@Assert\NotBlank` is itself a class reference.
     *
     * A name is taken only when the file determines it: fully qualified,
     * or with a first segment the file imports. An unimported short name
     * is left alone rather than assumed to live in the file's own
     * namespace — there is no reliable way to tell that from a typo, and
     * being silently wrong is worse than not extracting it. The same rule
     * is what keeps `int`, `list` and `@deprecated` out, so none of them
     * needs a keyword list to exclude it.
     *
     * @param list<Node>           $nodes
     * @param array<string,string> $imports short name => FQCN
     *
     * @return list<TypeReference>
     */
    private function collectDocBlockDependencies(array $nodes, array $imports): array
    {
        return $this->walk($nodes, fn (Node $node) => $this->docBlockTypesOf($node, $imports));
    }

    /**
     * @param array<string,string> $imports short name => FQCN
     *
     * @return list<TypeReference>
     */
    private function docBlockTypesOf(Node $node, array $imports): array
    {
        $docComment = $node->getDocComment();

        if (null === $docComment) {
            return [];
        }

        $text = $docComment->getText();

        if (!preg_match_all('/@([^\s(*]+)([^\r\n]*)/', $text, $tags, \PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $dependencies = [];

        foreach ($tags[1] as $i => [$tag, $offset]) {
            $written = \in_array($tag, self::TYPE_TAGS, true)
                ? $this->typeExpression($tags[2][$i][0])
                : $tag;

            // a docblock spans lines, and the tag's own is the one worth reporting
            $line = $docComment->getStartLine() + substr_count(substr($text, 0, $offset), "\n");

            foreach ($this->namesIn($written) as $name) {
                $fqcn = $this->resolveDocBlockName($name, $imports);

                if (null !== $fqcn) {
                    $dependencies[] = new TypeReference($fqcn, $line);
                }
            }
        }

        return $dependencies;
    }

    /**
     * What a tag opens with, before the parameter name or the prose:
     * `array<int, MyDto> $list the list` is `array<int,MyDto>`. The spaces
     * a type is allowed to contain are closed up first, so the expression
     * survives them and the description still stops it.
     */
    private function typeExpression(string $tagText): string
    {
        $closedUp = preg_replace(['/\s*([|&,<])\s*/', '/\s+>/'], ['$1', '>'], trim($tagText));

        return preg_split('/\s/', (string) $closedUp)[0];
    }

    /**
     * Every name-shaped run in a type expression, which is more than the
     * expression means: `array<int, MyDto>` yields all three. What isn't a
     * type falls out at resolution, so this doesn't have to know.
     *
     * @return list<string>
     */
    private function namesIn(string $expression): array
    {
        preg_match_all('/\\\\?'.self::NAME_SEGMENT.'(?:\\\\'.self::NAME_SEGMENT.')*/', $expression, $names);

        return $names[0];
    }

    /** @param array<string,string> $imports short name => FQCN */
    private function resolveDocBlockName(string $name, array $imports): ?string
    {
        if (str_starts_with($name, '\\')) {
            return substr($name, 1);
        }

        $segments = explode('\\', $name);
        $alias = array_shift($segments);

        if (!isset($imports[$alias])) {
            return null;
        }

        return implode('\\', [$imports[$alias], ...$segments]);
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
                    if ($this->importsAType($node, $item)) {
                        $imports[$item->alias?->toString() ?? $item->name->getLast()] = $item->name->toString();
                    }
                }
            }

            if ($node instanceof Node\Stmt\GroupUse) {
                foreach ($node->uses as $item) {
                    if ($this->importsAType($node, $item)) {
                        $imports[$item->alias?->toString() ?? $item->name->getLast()] = $node->prefix->toString().'\\'.$item->name->toString();
                    }
                }
            }

            $imports = [...$imports, ...$this->collectUseImports($this->children($node))];
        }

        return $imports;
    }

    /**
     * `use function` and `use const` import names that no type expression
     * can mean, and a group use states the kind per item.
     */
    private function importsAType(Node\Stmt\Use_|Node\Stmt\GroupUse $use, Node\UseItem $item): bool
    {
        return Node\Stmt\Use_::TYPE_NORMAL === ($item->type ?: $use->type);
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
