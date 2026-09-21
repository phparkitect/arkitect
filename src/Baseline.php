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
 *
 * Identities are counted, not just recorded: one class can reference a
 * forbidden name many times, one violation each, and an entry accepts one
 * of them, so a new reference is reported rather than covered by an old one.
 */
final class Baseline implements \Countable
{
    /** @var array<string, int> */
    private readonly array $known;

    /** @param array<string, int> $known */
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
            $identity = self::identify($violation);
            $known[$identity] = ($known[$identity] ?? 0) + 1;
        }

        return new self($known);
    }

    public static function fromJson(string $json): self
    {
        /** @var list<array{class: string, constraint: string, key: string|null}> $entries */
        $entries = json_decode($json, true, flags: \JSON_THROW_ON_ERROR);

        $known = [];

        foreach ($entries as $entry) {
            $identity = self::identityOf($entry['class'], $entry['constraint'], $entry['key']);
            $known[$identity] = ($known[$identity] ?? 0) + 1;
        }

        return new self($known);
    }

    public function contains(Violation $violation): bool
    {
        return isset($this->known[self::identify($violation)]);
    }

    /** One occurrence fewer, so the same entry cannot accept a second violation. */
    public function without(Violation $violation): self
    {
        $known = $this->known;
        $identity = self::identify($violation);

        if (1 < ($known[$identity] ?? 0)) {
            --$known[$identity];
        } else {
            unset($known[$identity]);
        }

        return new self($known);
    }

    /**
     * Sorted, because the file is committed and read in diffs: two runs over
     * the same violations have to produce the same bytes.
     */
    public function toJson(): string
    {
        $entries = [];

        foreach ($this->known as $identity => $occurrences) {
            [$class, $constraint, $key] = explode("\0", $identity);
            $entry = ['class' => $class, 'constraint' => $constraint, 'key' => '' === $key ? null : $key];
            array_push($entries, ...array_fill(0, $occurrences, $entry));
        }

        usort($entries, static fn (array $a, array $b) => array_values($a) <=> array_values($b));

        return json_encode($entries, \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR)."\n";
    }

    /**
     * Shrink only: the entries that still match something. Nothing is ever
     * added, so pruning cannot quietly accept work done since.
     */
    public function keepOnly(Violations $current): self
    {
        $kept = [];

        foreach ($current as $violation) {
            $identity = self::identify($violation);

            if (($kept[$identity] ?? 0) < ($this->known[$identity] ?? 0)) {
                $kept[$identity] = ($kept[$identity] ?? 0) + 1;
            }
        }

        return new self($kept);
    }

    public function count(): int
    {
        return array_sum($this->known);
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
