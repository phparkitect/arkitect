<?php

declare(strict_types=1);

namespace Arkitect\Cli;

/**
 * What the user typed, in the one form accepted: a command, then
 * `--flag` or `--option=value`.
 *
 * One spelling rather than four synonyms, and an unknown option is an
 * error — a mistyped flag that is silently ignored is a run that did
 * something other than what was asked.
 */
final class Arguments
{
    public const COMMANDS = ['check', 'generate-baseline', 'prune-baseline'];

    private const OPTIONS = ['config' => true, 'skip-baseline' => false, 'help' => false];

    /** @param array<string, string|true> $options */
    private function __construct(
        public readonly string $command,
        public readonly array $options,
    ) {
    }

    /**
     * @param list<string> $argv including the script name, as PHP provides it
     *
     * @throws \InvalidArgumentException on anything it cannot make sense of
     */
    public static function fromArgv(array $argv): self
    {
        $arguments = \array_slice($argv, 1);
        $command = 'check';
        $options = [];

        foreach ($arguments as $argument) {
            if (!str_starts_with($argument, '--')) {
                if (!\in_array($argument, self::COMMANDS, true)) {
                    throw new \InvalidArgumentException(\sprintf('Unknown command "%s". Expected one of: %s.', $argument, implode(', ', self::COMMANDS)));
                }

                $command = $argument;

                continue;
            }

            [$name, $value] = array_pad(explode('=', substr($argument, 2), 2), 2, null);

            if (!\array_key_exists($name, self::OPTIONS)) {
                throw new \InvalidArgumentException(\sprintf('Unknown option "--%s".', $name));
            }

            if (self::OPTIONS[$name] && null === $value) {
                throw new \InvalidArgumentException(\sprintf('Option "--%s" needs a value: --%s=…', $name, $name));
            }

            if (!self::OPTIONS[$name] && null !== $value) {
                throw new \InvalidArgumentException(\sprintf('Option "--%s" takes no value.', $name));
            }

            $options[$name] = $value ?? true;
        }

        return new self($command, $options);
    }

    public function has(string $option): bool
    {
        return \array_key_exists($option, $this->options);
    }

    public function value(string $option, string $default): string
    {
        $value = $this->options[$option] ?? $default;

        return true === $value ? $default : $value;
    }
}
