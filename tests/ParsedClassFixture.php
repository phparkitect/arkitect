<?php

declare(strict_types=1);

namespace Arkitect\Tests;

use Arkitect\Parser\ClassKind;
use Arkitect\Parser\ParsedClass;
use Arkitect\Parser\TypeReference;
use Arkitect\Parser\TypeReferences;

final class ParsedClassFixture
{
    /**
     * @param list<string>        $extends
     * @param list<string>        $implements
     * @param list<string>        $traits
     * @param array<string, int>  $dependencies FQCN => the line it is referenced on, since
     *                                          dependency violations are asserted per line
     */
    public static function create(
        string $fqcn,
        array $extends = [],
        array $implements = [],
        array $traits = [],
        array $dependencies = [],
        bool $isFinal = false,
        bool $isReadonly = false,
        bool $isAbstract = false,
        ClassKind $kind = ClassKind::RegularClass,
        int $line = 7,
        string $filePath = 'src/Foo.php',
    ): ParsedClass {
        return new ParsedClass(
            fqcn: $fqcn,
            line: $line,
            filePath: $filePath,
            kind: $kind,
            extends: self::references($extends, $line),
            implements: self::references($implements, $line),
            traits: self::references($traits, $line),
            dependencies: new TypeReferences(
                ...array_map(
                    static fn (string $name, int $at) => new TypeReference($name, $at),
                    array_keys($dependencies),
                    array_values($dependencies)
                )
            ),
            attributes: new TypeReferences(),
            docBlocks: [],
            isFinal: $isFinal,
            isReadonly: $isReadonly,
            isAbstract: $isAbstract,
        );
    }

    /** @param list<string> $names */
    private static function references(array $names, int $line): TypeReferences
    {
        return new TypeReferences(...array_map(static fn (string $n) => new TypeReference($n, $line), $names));
    }
}
