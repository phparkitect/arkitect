<?php

declare(strict_types=1);

namespace Arkitect\Parser;

use Arkitect\Parser\Internal\ClassCollector;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;

/**
 * Turns PHP source into a ParseResult. Pure: same input always produces the
 * same output, no filesystem access beyond the given content, no
 * reflection, no autoloading, no call into the PHP runtime being hosted on.
 * See ARCHITECTURE.md, stage 1.
 */
final class ClassParser
{
    public function parse(string $content, string $filePath, TargetPhpVersion $targetPhpVersion): ParseResult
    {
        $phpParser = (new ParserFactory())->createForVersion(
            PhpVersion::fromString($targetPhpVersion->toString())
        );

        $errorHandler = new Collecting();
        $stmts = $phpParser->parse($content, $errorHandler);

        $errors = [];
        foreach ($errorHandler->getErrors() as $error) {
            $errors[] = new ParsingError($filePath, $error->getMessage());
        }

        if (null === $stmts) {
            return new ParseResult([], $errors);
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());

        try {
            $stmts = $traverser->traverse($stmts);
            $classes = (new ClassCollector())->collect($stmts, $filePath);
        } catch (\Throwable $e) {
            $errors[] = new ParsingError($filePath, $e->getMessage());

            return new ParseResult([], $errors);
        }

        return new ParseResult($classes, $errors);
    }
}
