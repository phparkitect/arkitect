<?php

declare(strict_types=1);

namespace Arkitect\Command;

use Arkitect\BaselineRepository;
use Arkitect\Config;

/**
 * Drops the entries that no longer match anything, so a baseline shrinks as
 * a project is cleaned up instead of quietly keeping permission for work
 * already done.
 *
 * Shrink only, which is what makes it different from regenerating: a
 * violation introduced since is not accepted, it is left to fail.
 */
final class PruneBaseline
{
    public function __construct(
        private readonly Check $check,
        private readonly BaselineRepository $baselines,
    ) {
    }

    /** @return int how many entries were dropped */
    public function run(Config $config): int
    {
        $path = $config->baseline ?? throw new \RuntimeException('No baseline path in the config: there is nothing to prune.');

        $baseline = $this->baselines->read($path);
        $pruned = $baseline->keepOnly($this->check->run($config->withoutBaseline())->allViolations());

        $this->baselines->write($path, $pruned);

        return \count($baseline) - \count($pruned);
    }
}
