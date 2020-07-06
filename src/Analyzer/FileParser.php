<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use Psr\EventDispatcher\EventDispatcherInterface;

class FileParser implements Parser
{
    private $parser;

    private $traverser;

    private $fileVisitor;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $this->fileVisitor = new FileVisitor($eventDispatcher);
        $this->traverser = new NodeTraverser();

        $this->traverser->addVisitor(new NameResolver());
        $this->traverser->addVisitor($this->fileVisitor);
    }

    public function parse($file): void
    {
        $filePath = $file->getRelativePath();
        $fileContent = $file->getContents();

        try {
            $this->fileVisitor->setCurrentAnalisedFile($filePath);

            $stmts = $this->parser->parse($fileContent);

            $this->traverser->traverse($stmts);
        } catch (\Throwable $e) {
            echo 'Parse Error: ', $e->getMessage();
            print_r($e->getTraceAsString());
        }
    }
}
