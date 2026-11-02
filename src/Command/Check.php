<?php

declare(strict_types=1);

namespace Arkitect\Command;

use Arkitect\Baseline;
use Arkitect\BaselineRepository;
use Arkitect\Config;
use Arkitect\Evaluate\RuleResults;
use Arkitect\Evaluate\Violations;
use Arkitect\Parser\ParsingErrors;
use Arkitect\ProjectParser;
use Arkitect\Resolve\ParsedClassGraph;

/**
 * Walks a codebase through the stages and hands back what came out. Knows
 * nothing about where files come from or where results go: the parser is
 * injected, and the report is somebody else's business.
 */
final class Check
{
    public function __construct(
        private readonly ProjectParser $parser,
        private readonly BaselineRepository $baselines,
    ) {
    }

    public function run(Config $config): CheckResult
    {
        $parsed = $this->parser->parse($config->targetPhpVersion);

        // one parse, two views of it: names resolve against everything read,
        // while rules may only judge what the project itself wrote
        $graph = new ParsedClassGraph(...$parsed->classes);
        $ownClasses = $config->dependencies->ownClassesIn($parsed->classes);

        $baseline = null === $config->baseline
            ? Baseline::empty()
            : $this->baselines->read($config->baseline);

        $results = [];
        $silenced = 0;

        foreach ($config->rules as $rule) {
            $result = $rule->check($ownClasses, $graph);
            $kept = [];

            foreach ($result->violations as $violation) {
                if ($baseline->contains($violation)) {
                    $baseline = $baseline->without($violation);
                    ++$silenced;

                    continue;
                }

                $kept[] = $violation;
            }

            $results[] = $result->withViolations(new Violations(...$kept));
        }

        return new CheckResult(
            \count($ownClasses),
            new ParsingErrors(...$parsed->errors),
            new RuleResults(...$results),
            $silenced,
        );
    }
}
