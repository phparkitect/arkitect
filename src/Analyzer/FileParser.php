<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;

class FileParser implements Parser
{
    private \PhpParser\Parser $parser;

    private \PhpParser\NodeTraverser $traverser;

    private FileVisitor $fileVisitor;

    public function __construct()
    {
        $this->fileVisitor = new FileVisitor();

        $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->traverser = new NodeTraverser();
        $this->traverser->addVisitor(new NameResolver());
        $this->traverser->addVisitor($this->fileVisitor);
    }

    public function onClassAnalyzed(callable $callable): void
    {
        $this->fileVisitor->onClassAnalyzed($callable);
    }

    public function parse(string $fileContent): void
    {
        try {
            $stmts = $this->parser->parse($fileContent);

            $this->traverser->traverse($stmts);
        } catch (\Throwable $e) {
            echo 'Parse Error: ', $e->getMessage();
            print_r($e->getTraceAsString());
        }
    }
}
