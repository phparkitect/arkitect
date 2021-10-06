<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\Parser;

class FakeParser implements Parser
{
    public function parse(string $fileContent): void
    {
    }

    public function getClassDescriptions(): array
    {
        return [ClassDescription::build('uno')->get()];
    }
}
