<?php
declare(strict_types=1);

namespace Arkitect\DSL;

use Arkitect\Validation\Rule;

interface BecauseParser
{
    public function because(string $reason): Rule;
}
