<?php

declare(strict_types=1);

namespace Arkitect;

use Arkitect\Evaluate\Violation;
use Arkitect\Evaluate\Violations;

/**
 * The violations a project has decided to live with, so arkitect can be
 * adopted without fixing everything first.
 *
 * A violation is identified by the class, the constraint that produced it,
 * and the constraint's own `key` — never by its line, and never by its
 * message. A line moves whenever anything above it does, and a message is
 * prose we may reword; keying on either means a file that goes stale for
 * reasons that have nothing to do with the code it describes.
 */
final class Baseline implements \Countable
{
    /** @var array<string, true> */
    private readonly array $known;

    /** @param array<string, true> $known */
    private function __construct(array $known)
    {
        $this->known = $known;
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public static function of(Violations $violations): self
    {
        $known = [];

        foreach ($violations as $violation) {
            $known[self::identify($violation)] = true;
        }

        return new self($known);
    }

    public static function fromJson(string $json): self
    {
        /** @var list<array{class: string, constraint: string, key: string|null}> $entries */
        $entries = json_decode($json, true, flags: \JSON_THROW_ON_ERROR);

        $known = [];

        foreach ($entries as $entry) {
            $known[self::identityOf($entry['class'], $entry['constraint'], $entry['key'])] = true;
        }

        return new self($known);
    }

    public function contains(Violation $violation): bool
    {
        return isset($this->known[self::identify($violation)]);
    }

    /**
     * Sorted, because the file is committed and read in diffs: two runs over
     * the same violations have to produce the same bytes.
     */
    public function toJson(): string
    {
        $entries = [];

        foreach (array_keys($this->known) as $identity) {
            [$class, $constraint, $key] = explode("\0", $identity);
            $entries[] = ['class' => $class, 'constraint' => $constraint, 'key' => '' === $key ? null : $key];
        }

        usort($entries, static fn (array $a, array $b) => array_values($a) <=> array_values($b));

        return json_encode($entries, \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR)."\n";
    }

    public function count(): int
    {
        return \count($this->known);
    }

    private static function identify(Violation $violation): string
    {
        return self::identityOf($violation->fqcn, $violation->constraint, $violation->key);
    }

    private static function identityOf(string $class, string $constraint, ?string $key): string
    {
        // NUL cannot occur in any of the three, so it cannot be confused
        // with a separator inside one of them
        return implode("\0", [$class, $constraint, $key ?? '']);
    }
}
