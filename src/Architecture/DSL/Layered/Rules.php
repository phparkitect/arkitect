<?php
declare(strict_types=1);

namespace Arkitect\Architecture\DSL\Layered;

interface Rules
{
    /** @return iterable<array-key, \Arkitect\Rules\ArchRule> */
    public function rules(): iterable;
}
