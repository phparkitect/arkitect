<?php

declare(strict_types=1);

namespace Arkitect\Command;

use Arkitect\Baseline;
use Arkitect\BaselineRepository;
use Arkitect\Codebase;
use Arkitect\Config;
use Arkitect\Evaluate\RuleResult;
use Arkitect\Evaluate\RuleResults;
use Arkitect\Evaluate\Violations;
use Arkitect\Parser\ParsingErrors;
use Arkitect\ProjectParser;

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
        $codebase = Codebase::of($parsed);

        $baseline = null === $config->baseline
            ? Baseline::empty()
            : $this->baselines->read($config->baseline);

        $results = [];
        $silenced = 0;

        foreach ($config->rules as $rule) {
            $result = $rule->check($codebase->ownClasses, $codebase->graph);
            $kept = [];

            foreach ($result->violations as $violation) {
                if ($baseline->contains($violation)) {
                    ++$silenced;

                    continue;
                }

                $kept[] = $violation;
            }

            $results[] = new RuleResult(
                $result->because,
                $result->selected,
                $result->checked,
                new Violations(...$kept),
                $result->unresolved,
                $result->notApplicable,
            );
        }

        return new CheckResult(
            \count($codebase->ownClasses),
            new ParsingErrors(...$parsed->errors),
            new RuleResults(...$results),
            $silenced,
        );
    }
}
