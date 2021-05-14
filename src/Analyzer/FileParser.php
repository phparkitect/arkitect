<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

use PhpParser\Lexer\Emulative;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;

class FileParser implements Parser
{
    private const PHP_VERSION = '7.1';
    /** @var \PhpParser\Parser */
    private $parser;

    /** @var \PhpParser\NodeTraverser */
    private $traverser;

    /** @var FileVisitor */
    private $fileVisitor;

    public function __construct(NodeTraverser $traverser, FileVisitor $fileVisitor, NameResolver $nameResolver)
    {
        $this->fileVisitor = $fileVisitor;

        $lexer = new Emulative([
            'usedAttributes' => ['comments', 'startLine', 'endLine', 'startTokenPos', 'endTokenPos'],
             'phpVersion' => self::PHP_VERSION,
        ]);

        $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7, $lexer);
        $this->traverser = $traverser;
        $this->traverser->addVisitor($nameResolver);
        $this->traverser->addVisitor($this->fileVisitor);
    }

    public function getClassDescriptions(): array
    {
        return $this->fileVisitor->getClassDescriptions();
    }

    public function parse(string $fileContent): void
    {
        try {
            $this->fileVisitor->clearParsedClassDescriptions();

            $stmts = $this->parser->parse($fileContent);

            if (null === $stmts) {
                return;
            }

            $this->traverser->traverse($stmts);
        } catch (\Throwable $e) {
            echo 'Parse Error: ', $e->getMessage();
            print_r($e->getTraceAsString());
        }
    }
}
