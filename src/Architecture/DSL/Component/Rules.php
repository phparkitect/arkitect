<?php
declare(strict_types=1);

namespace Arkitect\Architecture\DSL\Component;

interface Rules
{
    /** @return iterable<array-key, \Arkitect\Rules\ArchRule> */
    public function rules(): iterable;
}
