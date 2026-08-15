<?php

declare(strict_types=1);

namespace Arkitect;

/**
 * A config before it has a root, which is the only state a config can be in
 * without being usable. `root()` is the only way out, so a config cannot
 * exist without one — the same reason `Rule` is reached only through
 * `because()`.
 *
 * The root is never inferred, neither from the working directory nor from
 * where the config file happens to sit: a run has to mean the same thing
 * wherever it was started from.
 */
final class ConfigDraft
{
    public function root(string $path): Config
    {
        return new Config($path, []);
    }
}
