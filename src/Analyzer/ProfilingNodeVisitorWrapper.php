<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

use PhpParser\Node;
use PhpParser\NodeVisitor;

/**
 * Wraps a NodeVisitor and measures the time spent in each callback.
 * Used by ProfilingFileParser to break down per-visitor cost during AST traversal.
 */
class ProfilingNodeVisitorWrapper implements NodeVisitor
{
    private NodeVisitor $inner;
    private string $name;
    private float $totalTime = 0.0;
    private int $enterNodeCalls = 0;
    private int $leaveNodeCalls = 0;

    public function __construct(NodeVisitor $inner, string $name)
    {
        $this->inner = $inner;
        $this->name = $name;
    }

    public function beforeTraverse(array $nodes): ?array
    {
        $start = microtime(true);
        $result = $this->inner->beforeTraverse($nodes);
        $this->totalTime += microtime(true) - $start;

        return $result;
    }

    public function enterNode(Node $node): int|Node|null
    {
        $start = microtime(true);
        $result = $this->inner->enterNode($node);
        $this->totalTime += microtime(true) - $start;
        $this->enterNodeCalls++;

        return $result;
    }

    public function leaveNode(Node $node): int|Node|array|null
    {
        $start = microtime(true);
        $result = $this->inner->leaveNode($node);
        $this->totalTime += microtime(true) - $start;
        $this->leaveNodeCalls++;

        return $result;
    }

    public function afterTraverse(array $nodes): ?array
    {
        $start = microtime(true);
        $result = $this->inner->afterTraverse($nodes);
        $this->totalTime += microtime(true) - $start;

        return $result;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTotalTime(): float
    {
        return $this->totalTime;
    }

    public function getEnterNodeCalls(): int
    {
        return $this->enterNodeCalls;
    }

    public function getLeaveNodeCalls(): int
    {
        return $this->leaveNodeCalls;
    }

    public function resetCounters(): void
    {
        $this->totalTime = 0.0;
        $this->enterNodeCalls = 0;
        $this->leaveNodeCalls = 0;
    }

    public function getInner(): NodeVisitor
    {
        return $this->inner;
    }
}
