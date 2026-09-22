<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;

class DocblockParserFactory
{
    /**
     * Psalm only ever sees one of the two installed versions of phpstan/phpdoc-parser,
     * so the constructor calls of the other branch always look wrong to it.
     *
     * @psalm-suppress TooFewArguments
     * @psalm-suppress TooManyArguments
     * @psalm-suppress InvalidArgument
     */
    public static function create(): DocblockParser
    {
        $phpDocParser = null;
        $phpDocLexer = null;

        // this if is to allow using v1 or v2
        if (class_exists(ParserConfig::class)) {
            $parserConfig = new ParserConfig(['lines' => true]);
            $constExprParser = new ConstExprParser($parserConfig);
            $typeParser = new TypeParser($parserConfig, $constExprParser);
            $phpDocParser = new PhpDocParser($parserConfig, $typeParser, $constExprParser);
            $phpDocLexer = new Lexer($parserConfig);
        } else {
            $typeParser = new TypeParser();
            $constExprParser = new ConstExprParser();
            // on v1 line numbers are opt-in through $usedAttributes, the 5th constructor
            // argument: without it every node reports no line and violations would be
            // reported on the wrong line
            $phpDocParser = new PhpDocParser($typeParser, $constExprParser, false, false, ['lines' => true]);
            $phpDocLexer = new Lexer();
        }

        return new DocblockParser($phpDocParser, $phpDocLexer);
    }
}
