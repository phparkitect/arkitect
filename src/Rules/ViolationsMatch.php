<?php

declare(strict_types=1);

namespace Arkitect\Rules;

/**
 * The outcome of pairing the violations of a run with the entries of a
 * baseline: what is already known, what is new, and what the baseline
 * still claims but nothing matches anymore.
 */
class ViolationsMatch
{
    private Violations $known;

    private Violations $new;

    private Violations $stale;

    public function __construct(Violations $known, Violations $new, Violations $stale)
    {
        $this->known = $known;
        $this->new = $new;
        $this->stale = $stale;
    }

    /**
     * The current violations that matched a baseline entry, with the line
     * numbers of the current run.
     */
    public function known(): Violations
    {
        return $this->known;
    }

    /**
     * The current violations no baseline entry matched.
     */
    public function new(): Violations
    {
        return $this->new;
    }

    /**
     * The baseline entries no current violation matched.
     */
    public function stale(): Violations
    {
        return $this->stale;
    }
}
