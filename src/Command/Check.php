<?php

declare(strict_types=1);

namespace Arkitect\Command;

use Arkitect\Codebase;
use Arkitect\Config;
use Arkitect\Evaluate\RuleResults;
use Arkitect\Parser\ParsingErrors;
use Arkitect\ProjectParser;

/**
 * Walks a codebase through the stages and hands back what came out. Knows
 * nothing about where files come from or where results go: the parser is
 * injected, and the report is somebody else's business.
 */
final class Check
{
    public function __construct(private readonly ProjectParser $parser)
    {
    }

    public function run(Config $config): CheckResult
    {
        $parsed = $this->parser->parse($config->targetPhpVersion);
        $codebase = Codebase::of($parsed);

        $results = [];

        foreach ($config->rules as $rule) {
            $results[] = $rule->check($codebase->ownClasses, $codebase->graph);
        }

        return new CheckResult(
            \count($codebase->ownClasses),
            new ParsingErrors(...$parsed->errors),
            new RuleResults(...$results),
        );
    }
}
