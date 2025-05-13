<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;

class DocblockParser
{
    private PhpDocParser $innerParser;

    private Lexer $innerLexer;

    public function __construct(PhpDocParser $innerParser, Lexer $innerLexer)
    {
        $this->innerParser = $innerParser;
        $this->innerLexer = $innerLexer;
    }

    public function parse(string $docblock): PhpDocNode
    {
        $tokens = $this->innerLexer->tokenize($docblock);
        $tokenIterator = new TokenIterator($tokens);

        return $this->innerParser->parse($tokenIterator);
    }
}
