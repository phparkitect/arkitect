<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

use PhpParser\Node;
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

    public function parse($file, array $excludedFiles = []): void
    {
        $filePath = $file->getRelativePath();
        $fileContent = $file->getContents();

        try {
            $this->fileVisitor->setCurrentAnalisedFile($filePath);

            $stmts = $this->parser->parse($fileContent);

            if (!$this->shouldExcludeFile($stmts, $file->getFilename(), $excludedFiles)) {
                $this->traverser->traverse($stmts);
            }
        } catch (\Throwable $e) {
            echo 'Parse Error: ', $e->getMessage();
            print_r($e->getTraceAsString());
        }
    }

    private function shouldExcludeFile(array $stmts, string $filename, array $excludedFiles): bool
    {
        foreach ($stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Namespace_) {
                $classDescriptionBuilder = ClassDescriptionBuilder::create(
                    $stmt->name->toCodeString(),
                    $filename
                );

                $file = $classDescriptionBuilder->get()->getFQCN() .
                    '\\' . str_replace('.php', '', $filename);

                return in_array($file, $excludedFiles);
            }
        }

        return false;
    }
}
