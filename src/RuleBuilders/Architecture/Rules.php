<?php
declare(strict_types=1);

namespace Arkitect\RuleBuilders\Architecture;

use Arkitect\Rules\DSL\ArchRule;

interface Rules
{
    /** @return iterable<array-key, ArchRule> */
    public function rules(): iterable;
}
