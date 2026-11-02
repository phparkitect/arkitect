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
 *
 * With nothing declared it writes to `Config::DEFAULT_BASELINE`, so the
 * first command someone runs on a project full of violations works without
 * editing a config first.
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
        $path = $config->baseline() ?? Config::DEFAULT_BASELINE;

        $baseline = Baseline::of($this->check->run($config->withoutBaseline())->allViolations());

        $this->baselines->write($path, $baseline);

        return \count($baseline);
    }
}
