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

    public function parse(string $fileContent, string $filename): ClassDescriptions|ParsingErrors|GenericError
    {
        try {
            $this->fileVisitor->clearParsedClassDescriptions();
            $this->fileVisitor->setFilePath($filename);

            $errorHandler = new Collecting();
            $stmts = $this->parser->parse($fileContent, $errorHandler);

            if ($errorHandler->hasErrors()) {
                $parsingErrors = new ParsingErrors();
                foreach ($errorHandler->getErrors() as $error) {
                    $parsingErrors->add(ParsingError::create($filename, $error->getMessage()));
                }

                return $parsingErrors;
            }

            if (null === $stmts) {
                return new ClassDescriptions();
            }

            $this->traverser->traverse($stmts);

            return new ClassDescriptions($this->fileVisitor->getClassDescriptions());
        } catch (\Throwable $e) {
            return GenericError::create($filename, $e->getMessage());
        }
    }
}
