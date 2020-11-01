<?php
declare(strict_types=1);

namespace Arkitect\DSL;

use Arkitect\Expression\Expression;

interface ThatParser
{
    public function that(Expression $expression): AndThatShouldParser;
}
