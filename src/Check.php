<?php

declare(strict_types=1);

namespace Arkitect;

use Arkitect\Evaluate\RuleResults;
use Arkitect\Evaluate\Rules;
use Arkitect\Parser\TargetPhpVersion;

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

    public function run(Rules $rules, TargetPhpVersion $version): CheckResult
    {
        $parsed = $this->parser->parse($version);
        $codebase = Codebase::of($parsed);

        $results = [];

        foreach ($rules as $rule) {
            $results[] = $rule->check($codebase->ownClasses, $codebase->graph);
        }

        return new CheckResult(
            \count($codebase->ownClasses),
            new Parser\ParsingErrors(...$parsed->errors),
            new RuleResults(...$results),
        );
    }
}
