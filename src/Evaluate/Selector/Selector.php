<?php

declare(strict_types=1);

namespace Arkitect\Evaluate\Selector;

use Arkitect\Parser\ParsedClass;
use Arkitect\Resolve\ClassGraph;

/**
 * What a rule talks about, as opposed to what it requires. A selector never
 * produces a violation, and nothing in this namespace knows Violation
 * exists — run.php checks that as a rule.
 */
interface Selector
{
    public function matches(ParsedClass $class, ClassGraph $classGraph): Selection;
}
