<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Rules\ParsingError;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser as PhpParser;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;

/**
 * A FileParser that instruments each sub-phase of parsing:
 * - AST construction (nikic/php-parser tokenize + parse)
 * - Traversal time per visitor (NameResolver, DocblockTypesResolver, FileVisitor)
 */
class ProfilingFileParser implements Parser
{
    private PhpParser $parser;
    private NodeTraverser $traverser;
    private FileVisitor $fileVisitor;

    /** @var array<ParsingError> */
    private array $parsingErrors = [];

    /** @var ProfilingNodeVisitorWrapper[] */
    private array $wrappedVisitors = [];

    private float $totalAstTime = 0.0;
    private float $totalTraversalTime = 0.0;
    private int $totalNodeCount = 0;

    /** @var array<string, float> */
    private array $perFileAstTime = [];
    /** @var array<string, float> */
    private array $perFileTraversalTime = [];
    /** @var array<string, array<string, float>> */
    private array $perFileVisitorTime = [];
    /** @var array<string, int> */
    private array $perFileNodeCount = [];

    private string $currentFile = '';

    public function __construct(
        TargetPhpVersion $targetPhpVersion,
        bool $parseCustomAnnotations = true,
    ) {
        $this->parser = (new ParserFactory())->createForVersion(
            PhpVersion::fromString($targetPhpVersion->get())
        );

        $nameResolver = new NameResolver();
        $docblockResolver = new DocblockTypesResolver($parseCustomAnnotations);
        $this->fileVisitor = new FileVisitor(new ClassDescriptionBuilder());

        // Wrap each visitor with profiling
        $wrappedNameResolver = new ProfilingNodeVisitorWrapper($nameResolver, 'NameResolver');
        $wrappedDocblock = new ProfilingNodeVisitorWrapper($docblockResolver, 'DocblockTypesResolver');
        $wrappedFileVisitor = new ProfilingNodeVisitorWrapper($this->fileVisitor, 'FileVisitor');

        $this->wrappedVisitors = [
            $wrappedNameResolver,
            $wrappedDocblock,
            $wrappedFileVisitor,
        ];

        $this->traverser = new NodeTraverser();
        $this->traverser->addVisitor($wrappedNameResolver);
        $this->traverser->addVisitor($wrappedDocblock);
        $this->traverser->addVisitor($wrappedFileVisitor);
    }

    public function parse(string $fileContent, string $filename): void
    {
        $this->parsingErrors = [];
        $this->currentFile = $filename;

        try {
            $this->fileVisitor->clearParsedClassDescriptions();
            $this->fileVisitor->setFilePath($filename);

            // Reset per-file visitor counters
            foreach ($this->wrappedVisitors as $w) {
                $w->resetCounters();
            }

            // --- Phase 1: AST construction ---
            $errorHandler = new Collecting();
            $astStart = microtime(true);
            $stmts = $this->parser->parse($fileContent, $errorHandler);
            $astTime = microtime(true) - $astStart;

            $this->totalAstTime += $astTime;
            $this->perFileAstTime[$filename] = $astTime;

            if ($errorHandler->hasErrors()) {
                foreach ($errorHandler->getErrors() as $error) {
                    $this->parsingErrors[] = ParsingError::create($filename, $error->getMessage());
                }
            }

            if (null === $stmts) {
                $this->perFileTraversalTime[$filename] = 0.0;
                $this->perFileVisitorTime[$filename] = [];
                $this->perFileNodeCount[$filename] = 0;

                return;
            }

            // Count AST nodes
            $nodeCount = $this->countNodes($stmts);
            $this->totalNodeCount += $nodeCount;
            $this->perFileNodeCount[$filename] = $nodeCount;

            // --- Phase 2: Traversal ---
            $traversalStart = microtime(true);
            $this->traverser->traverse($stmts);
            $traversalTime = microtime(true) - $traversalStart;

            $this->totalTraversalTime += $traversalTime;
            $this->perFileTraversalTime[$filename] = $traversalTime;

            // Record per-visitor time for this file
            $visitorTimes = [];
            foreach ($this->wrappedVisitors as $w) {
                $visitorTimes[$w->getName()] = $w->getTotalTime();
            }
            $this->perFileVisitorTime[$filename] = $visitorTimes;
        } catch (\Throwable $e) {
            echo 'Parse Error: ', $e->getMessage();
            print_r($e->getTraceAsString());
        }
    }

    public function getClassDescriptions(): array
    {
        return $this->fileVisitor->getClassDescriptions();
    }

    public function getParsingErrors(): array
    {
        return $this->parsingErrors;
    }

    // --- Profiling accessors ---

    public function getTotalAstTime(): float
    {
        return $this->totalAstTime;
    }

    public function getTotalTraversalTime(): float
    {
        return $this->totalTraversalTime;
    }

    public function getTotalNodeCount(): int
    {
        return $this->totalNodeCount;
    }

    /**
     * @return array<string, float> visitor name => total cumulative time
     */
    public function getVisitorTotals(): array
    {
        $totals = [];
        foreach ($this->wrappedVisitors as $w) {
            // These are cumulative across all files (not reset between files)
            // We accumulate from per-file data instead
            $totals[$w->getName()] = 0.0;
        }
        foreach ($this->perFileVisitorTime as $visitors) {
            foreach ($visitors as $name => $time) {
                $totals[$name] += $time;
            }
        }

        return $totals;
    }

    /**
     * @return array<string, float> filename => AST parse time
     */
    public function getPerFileAstTime(): array
    {
        return $this->perFileAstTime;
    }

    /**
     * @return array<string, float> filename => traversal time
     */
    public function getPerFileTraversalTime(): array
    {
        return $this->perFileTraversalTime;
    }

    /**
     * @return array<string, array<string, float>> filename => [visitor => time]
     */
    public function getPerFileVisitorTime(): array
    {
        return $this->perFileVisitorTime;
    }

    /**
     * @return array<string, int> filename => node count
     */
    public function getPerFileNodeCount(): array
    {
        return $this->perFileNodeCount;
    }

    /**
     * @param array<\PhpParser\Node\Stmt> $stmts
     */
    private function countNodes(array $stmts): int
    {
        $count = 0;
        foreach ($stmts as $stmt) {
            $count += $this->countNode($stmt);
        }

        return $count;
    }

    private function countNode(\PhpParser\Node $node): int
    {
        $count = 1;
        foreach ($node->getSubNodeNames() as $name) {
            $subNode = $node->$name;
            if ($subNode instanceof \PhpParser\Node) {
                $count += $this->countNode($subNode);
            } elseif (\is_array($subNode)) {
                foreach ($subNode as $child) {
                    if ($child instanceof \PhpParser\Node) {
                        $count += $this->countNode($child);
                    }
                }
            }
        }

        return $count;
    }
}
