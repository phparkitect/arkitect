<?php

declare(strict_types=1);

namespace Arkitect\CLI;

/**
 * The messages printed when a deprecated option is used.
 */
class DeprecationNotice
{
    public const IGNORE_BASELINE_LINENUMBERS = '⚠️  `--ignore-baseline-linenumbers` / `ignoreBaselineLinenumbers()` is deprecated and has no effect: baseline matching now tolerates violations moved by edits elsewhere in the file. It will be removed in the next major version.';
}
