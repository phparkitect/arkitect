<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Rules\ParsingError;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;

class FileParser implements Parser
{
    /** @var \PhpParser\Parser */
    private $parser;

    private NodeTraverser $traverser;

    private FileVisitor $fileVisitor;

    private array $parsingErrors;

    public function __construct(
        NodeTraverser $traverser,
        FileVisitor $fileVisitor,
        NameResolver $nameResolver,
        TargetPhpVersion $targetPhpVersion,
    ) {
        $this->fileVisitor = $fileVisitor;
        $this->parsingErrors = [];

        $this->parser = (new ParserFactory())->createForVersion(PhpVersion::fromString($targetPhpVersion->get() ?? phpversion()));
        $this->traverser = $traverser;
        $this->traverser->addVisitor($nameResolver);
        $this->traverser->addVisitor($this->fileVisitor);
    }

    /**
     * @return ClassDescription[]
     */
    public function getClassDescriptions(): array
    {
        return $this->fileVisitor->getClassDescriptions();
    }

    public function parse(string $fileContent, string $filename): void
    {
        $this->parsingErrors = [];
        try {
            $this->fileVisitor->clearParsedClassDescriptions();

            $errorHandler = new Collecting();
            $stmts = $this->parser->parse($fileContent, $errorHandler);

            if ($errorHandler->hasErrors()) {
                foreach ($errorHandler->getErrors() as $error) {
                    $this->parsingErrors[] = ParsingError::create($filename, $error->getMessage());
                }
            }

            if (null === $stmts) {
                return;
            }

            $this->traverser->traverse($stmts);
        } catch (\Throwable $e) {
            echo 'Parse Error: ', $e->getMessage();
            print_r($e->getTraceAsString());
        }
    }

    public function getParsingErrors(): array
    {
        return $this->parsingErrors;
    }
}
