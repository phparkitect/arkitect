<?php

declare(strict_types=1);

namespace Arkitect;

use Arkitect\Parser\ParseResult;
use Arkitect\Parser\TargetPhpVersion;

/**
 * Everything a run needs from parsing: the classes, and what could not be
 * read. An implementation decides where the files come from and whether
 * anything is reused between runs.
 */
interface ProjectParser
{
    public function parse(TargetPhpVersion $version): ParseResult;
}
