<?php
declare(strict_types=1);

namespace Arkitect\Rules\DSL;

use Arkitect\Expression\Expression;

interface ThatParser
{
    public function that(Expression $expression): AndThatShouldParser;
}
