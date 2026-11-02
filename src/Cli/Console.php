<?php

declare(strict_types=1);

namespace Arkitect\Cli;

use Arkitect\Command\Check;
use Arkitect\Command\GenerateBaseline;
use Arkitect\Command\PruneBaseline;
use Arkitect\Config;
use Arkitect\FileSystem\FilesystemBaselineRepository;
use Arkitect\FileSystem\FilesystemFileRepository;
use Arkitect\Parser\RepositoryParser;
use Arkitect\Report\GitlabReport;
use Arkitect\Report\Report;
use Arkitect\Report\TextReport;

/**
 * The driving side of the hexagon: it translates a terminal into a run and
 * a run back into a terminal, and holds no decision of its own. Everything
 * it builds is a concrete adapter — this is the composition root.
 */
final class Console
{
    private const CLEAN = 0;
    private const FAILED = 1;
    private const MISUSED = 2;

    /**
     * @param list<string> $argv
     *
     * @return int the exit code
     */
    public function run(array $argv, mixed $out = \STDOUT, mixed $err = \STDERR): int
    {
        try {
            $arguments = Arguments::fromArgv($argv);

            if ($arguments->has('help')) {
                fwrite($out, $this->help());

                return self::CLEAN;
            }

            $report = $this->reportFor($arguments->value('format', 'text'));
            $config = $this->withBaselineFound($this->configFrom($arguments->value('config', 'phparkitect.php')));
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            fwrite($err, $e->getMessage()."\n");

            return self::MISUSED;
        }

        if ($arguments->has('skip-baseline')) {
            $config = $config->withoutBaseline();
        }

        $check = new Check(
            new RepositoryParser(new FilesystemFileRepository($config->root())),
            $baselines = new FilesystemBaselineRepository($config->root()),
        );

        try {
            return match ($arguments->command) {
                'generate-baseline' => $this->report(
                    $out,
                    \sprintf("accepted %d violations\n", (new GenerateBaseline($check, $baselines))->run($config))
                ),
                'prune-baseline' => $this->report(
                    $out,
                    \sprintf("dropped %d entries that no longer matched\n", (new PruneBaseline($check, $baselines))->run($config))
                ),
                default => $this->check($check, $config, $report, $out),
            };
        } catch (\RuntimeException $e) {
            fwrite($err, $e->getMessage()."\n");

            return self::MISUSED;
        }
    }

    private function check(Check $check, Config $config, Report $report, mixed $out): int
    {
        $result = $check->run($config);

        fwrite($out, $report->render($result)."\n");

        return $result->isClean() ? self::CLEAN : self::FAILED;
    }

    private function report(mixed $out, string $message): int
    {
        fwrite($out, $message);

        return self::CLEAN;
    }

    /**
     * A baseline sitting at the conventional name is used without being
     * declared, which is what makes `generate-baseline` then `check` work
     * with nothing in between. Only when it is actually there: a path in
     * the config must exist, a convention is allowed not to.
     */
    private function withBaselineFound(Config $config): Config
    {
        if (null !== $config->baseline()) {
            return $config;
        }

        return is_file($config->root().'/'.Config::DEFAULT_BASELINE)
            ? $config->withBaseline(Config::DEFAULT_BASELINE)
            : $config;
    }

    private function reportFor(string $format): Report
    {
        return match ($format) {
            'text' => new TextReport(),
            'gitlab' => new GitlabReport(),
            default => throw new \InvalidArgumentException(\sprintf('Unknown format "%s". Expected text or gitlab.', $format)),
        };
    }

    /**
     * The config file is PHP that returns a Config, so loading it runs the
     * user's own code — which is why this lives on the driving side and not
     * behind a port.
     */
    private function configFrom(string $path): Config
    {
        if (!is_file($path)) {
            throw new \RuntimeException(\sprintf('No config at "%s". Point --config at one, or write it there.', $path));
        }

        $config = require $path;

        if (!$config instanceof Config) {
            throw new \RuntimeException(\sprintf('"%s" has to return a Config, and returned %s.', $path, get_debug_type($config)));
        }

        return $config;
    }

    private function help(): string
    {
        return <<<'HELP'
            arkitect [command] [options]

              check               report violations (the default)
              generate-baseline   accept every violation there is now
              prune-baseline      drop entries that no longer match anything

              --config=PATH       where the config is (default: phparkitect.php)
              --format=NAME       text (the default) or gitlab
              --skip-baseline     ignore the baseline, default one included
              --help              this

            HELP;
    }
}
