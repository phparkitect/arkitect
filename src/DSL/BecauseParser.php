<?php
declare(strict_types=1);

namespace Arkitect\DSL;

interface BecauseParser extends GetParser
{
    public function because(string $reason): GetParser;
}
