<?php

declare(strict_types=1);

namespace Arkitect\Command;

use Arkitect\Baseline;
use Arkitect\BaselineRepository;
use Arkitect\Config;

/**
 * Accepts the project as it stands, so arkitect can be adopted without
 * fixing everything first.
 *
 * It runs the check with any existing baseline ignored: regenerating means
 * "accept what is here now", and reading the old one would hide from it
 * exactly the violations it is meant to record.
 */
final class GenerateBaseline
{
    public function __construct(
        private readonly Check $check,
        private readonly BaselineRepository $baselines,
    ) {
    }

    /** @return int how many violations were accepted */
    public function run(Config $config): int
    {
        $path = $config->baseline ?? throw new \RuntimeException('No baseline path in the config: add baseline() to say where it goes.');

        $baseline = Baseline::of($this->check->run($config->withoutBaseline())->allViolations());

        $this->baselines->write($path, $baseline);

        return \count($baseline);
    }
}
