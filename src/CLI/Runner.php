<?php

declare(strict_types=1);

namespace Arkitect\CLI;

use Arkitect\Rules\RuleChecker;
use Arkitect\Rules\Violations;

class Runner
{
    public function run(Config $config): Violations
    {
        $violations = [];
        $classSetRules = $config->getClassSetRules();

        foreach ($classSetRules as $classSetRule) {
            $ruleChecker = RuleChecker::build($classSetRule->getClassSet(), ...$classSetRule->getRules());
            $violations = array_merge($violations, $ruleChecker->run()->toArray());
        }

        return new Violations(...$violations);
    }
}
