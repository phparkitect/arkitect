<?php

declare(strict_types=1);

namespace Arkitect\Report;

use Arkitect\Command\CheckResult;

/**
 * How results leave the system. The text one is written for a human reading
 * a terminal; a machine-readable one is the reason this is an interface.
 */
interface Report
{
    public function render(CheckResult $check): string;
}
