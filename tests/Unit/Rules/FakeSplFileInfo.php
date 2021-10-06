<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use Symfony\Component\Finder\SplFileInfo;

class FakeSplFileInfo extends SplFileInfo
{
    public function getContents(): string
    {
        return '';
    }
}
