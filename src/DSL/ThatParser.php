<?php
declare(strict_types=1);

namespace Arkitect\DSL;

interface ThatParser
{
    public function that(Expression $expression): AndThatShouldParser;
}
