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
     * @psalm-suppress TooFewArguments
     * @psalm-suppress InvalidArgument
     */
    public static function create(): DocblockParser
    {
        $phpDocParser = null;
        $phpDocLexer = null;

        // this if is to allow using v 1.2 or v2
        if (class_exists(ParserConfig::class)) {
            $parserConfig = new ParserConfig(['lines' => true]);
            $constExprParser = new ConstExprParser($parserConfig);
            $typeParser = new TypeParser($parserConfig, $constExprParser);
            $phpDocParser = new PhpDocParser($parserConfig, $typeParser, $constExprParser);
            $phpDocLexer = new Lexer($parserConfig);
        } else {
            $typeParser = new TypeParser();
            $constExprParser = new ConstExprParser();
            $phpDocParser = new PhpDocParser($typeParser, $constExprParser);
            $phpDocLexer = new Lexer();
        }

        return new DocblockParser($phpDocParser, $phpDocLexer);
    }
}
