<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

use PhpParser\NameContext;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ErrorHandler;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;

/**
 * This class is used to collect type information from dockblocks, in particular
 * - regular dockblock tags: @param, @var, @return
 * - old style annotations like @Assert\Blank 
 * and assign them to the piece of code the dockblock is attached to. 
 * 
 * This allows to detect dependencies declared only in dockblocks
 * 
 * Since the @throws tags does not have any corresponding code, we populate a custom node attribute in order to make it available
 * to subsequent visitors.
 */
class DocblockTypesResolver extends NodeVisitorAbstract
{   
    private NameContext $nameContext;

    private bool $parseCustomAnnotations;

    public function __construct(bool $parseCustomAnnotations = true)
    {
        $this->nameContext = new NameContext(new ErrorHandler\Throwing());
        $this->parseCustomAnnotations = $parseCustomAnnotations;
    }

    public function beforeTraverse(array $nodes): ?array
    {
        // this also clears the name context so there is not need to reinstantiate it
        $this->nameContext->startNamespace();

        return null;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Stmt\Namespace_) {
            $this->nameContext->startNamespace($node->name);
        } 

        if ($node instanceof Stmt\Use_) {
            foreach ($node->uses as $use) {
                $this->addAlias($use, $node->type, null);
            }
        } 
        
        if ($node instanceof Stmt\GroupUse) {
            foreach ($node->uses as $use) {
                $this->addAlias($use, $node->type, $node->prefix);
            }
        }    

        // properties
        // methods/function/anoynomous functions
        
    }

    public function afterTraverse(array $nodes)
    {
        dump($this->nameContext);
    }

    private function addAlias(Node\UseItem $use, int $type, ?Name $prefix = null): void
    {
        // Add prefix for group uses
        $name = $prefix ? Name::concat($prefix, $use->name) : $use->name;
        // Type is determined either by individual element or whole use declaration
        $type |= $use->type;

        $this->nameContext->addAlias(
            $name,
            (string) $use->getAlias(),
            $type,
            $use->getAttributes()
        );
    }

}