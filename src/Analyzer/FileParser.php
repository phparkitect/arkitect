<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

use Arkitect\CLI\TargetPhpVersion;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser as PhpParser;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;

class FileParser implements Parser
{
    private PhpParser $parser;

    private NodeTraverser $traverser;

    private FileVisitor $fileVisitor;

    public function __construct(
        NodeTraverser $traverser,
        FileVisitor $fileVisitor,
        NameResolver $nameResolver,
        DocblockTypesResolver $docblockTypesResolver,
        TargetPhpVersion $targetPhpVersion,
    ) {
        $this->fileVisitor = $fileVisitor;

        $this->parser = (new ParserFactory())->createForVersion(PhpVersion::fromString($targetPhpVersion->get()));
        $this->traverser = $traverser;
        $this->traverser->addVisitor($nameResolver);
        $this->traverser->addVisitor($docblockTypesResolver);
        $this->traverser->addVisitor($this->fileVisitor);
    }

    public function parse(string $fileContent, string $filename): ParserResult
    {
        $this->fileVisitor->clearParsedClassDescriptions();
        $this->fileVisitor->setFilePath($filename);

        $errorHandler = new Collecting();
        $parsingErrors = new ParsingErrors();

        $stmts = $this->parser->parse($fileContent, $errorHandler);

        foreach ($errorHandler->getErrors() as $error) {
            $parsingErrors->add(ParsingError::create($filename, $error->getMessage()));
        }

        try {
            $this->traverser->traverse($stmts);
        } catch (\Throwable $e) {
            $parsingErrors->add(ParsingError::create($filename, $e->getMessage()));

            return ParserResult::withParsingErrors($parsingErrors);
        }

        return ParserResult::create(
            new ClassDescriptions($this->fileVisitor->getClassDescriptions()),
            $parsingErrors
        );
    }
}
