<?php
declare(strict_types=1);

namespace Arkitect\DSL;

use Arkitect\Validation\Rule;

interface GetParser
{
    public function get(): Rule;
}
