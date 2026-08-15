<?php

declare(strict_types=1);

namespace Arkitect\Report;

use Arkitect\Evaluate\RuleResult;
use Arkitect\Parser\ParseResult;

/**
 * Written for the run that fails, since that is the only one anybody reads.
 * A clean run is one line.
 *
 * Violations are grouped under the rule's reason so it is read once rather
 * than repeated on every line, which is what makes a 400-violation report
 * unreadable in v1. Each line leads with `file:line` because that is the
 * part terminals and IDEs turn into a link, and everything is ordered by
 * file then line so two runs over the same code produce the same bytes.
 *
 * Rules that pass are not printed at all: ten green lines hide the two red
 * ones, and the summary already says how many rules ran.
 */
final class TextReport
{
    /** @param list<RuleResult> $results */
    public function render(ParseResult $parsed, array $results): string
    {
        $sections = array_filter([
            $this->violations($results),
            $this->unresolved($results),
            $this->uselessRules($results),
            $this->parsingErrors($parsed),
            $this->summary($parsed, $results),
        ], static fn (string $section) => '' !== $section);

        return implode("\n\n", $sections);
    }

    /** @param list<RuleResult> $results */
    private function violations(array $results): string
    {
        $groups = [];

        foreach ($results as $result) {
            if (0 === \count($result->violations)) {
                continue;
            }

            $lines = [];
            foreach ($result->violations as $violation) {
                // no class name: file:line already identifies it, and on real
                // paths the repeated FQCN was two thirds of every line
                $lines[] = [
                    \sprintf('%s:%d', $violation->filePath, $violation->line),
                    $violation->detail,
                ];
            }

            $groups[] = \sprintf("✗ %s\n%s", $result->because, $this->aligned($lines));
        }

        return implode("\n\n", $groups);
    }

    /** @param list<RuleResult> $results */
    private function unresolved(array $results): string
    {
        $lines = [];

        foreach ($results as $result) {
            foreach ($result->unresolved as $unresolved) {
                $lines[] = [
                    \sprintf('%s:%d', $unresolved->filePath, $unresolved->line),
                    $unresolved->detail,
                ];
            }
        }

        if ([] === $lines) {
            return '';
        }

        return \sprintf(
            "! could not check %s\n%s\n  vendor/ is probably outside the analysed root",
            $this->plural(\count($lines), 'class', 'classes'),
            $this->aligned($lines)
        );
    }

    /**
     * The two ways a rule can look like it protects something while
     * protecting nothing. Kept loud for that reason, and separate from each
     * other because one is fixed in the that() and the other in the should().
     *
     * @param list<RuleResult> $results
     */
    private function uselessRules(array $results): string
    {
        $lines = [];

        foreach ($results as $result) {
            if ($result->matchedNothing()) {
                $lines[] = \sprintf('! "%s" matched no classes', $result->because);

                continue;
            }

            if ($result->judgedNothing()) {
                $lines[] = \sprintf(
                    '! "%s" matched %s and judged none',
                    $result->because,
                    $this->plural($result->selected, 'class', 'classes')
                );
            }
        }

        return implode("\n", $lines);
    }

    private function parsingErrors(ParseResult $parsed): string
    {
        if ([] === $parsed->errors) {
            return '';
        }

        $lines = [];
        foreach ($parsed->errors as $error) {
            $lines[] = [$error->filePath, $error->message];
        }

        return \sprintf(
            "! could not parse %s\n%s",
            $this->plural(\count($parsed->errors), 'file', 'files'),
            $this->aligned($lines)
        );
    }

    /** @param list<RuleResult> $results */
    private function summary(ParseResult $parsed, array $results): string
    {
        $violations = 0;
        $failingRules = 0;

        foreach ($results as $result) {
            $found = \count($result->violations);
            $violations += $found;
            $failingRules += $found > 0 ? 1 : 0;
        }

        $counts = \sprintf(
            '%s, %s',
            $this->plural(\count($parsed->classes), 'class', 'classes'),
            $this->plural(\count($results), 'rule', 'rules')
        );

        if (0 === $violations) {
            return \sprintf('✓ %s, no violations', $counts);
        }

        return \sprintf(
            '%s, %s in %s',
            $counts,
            $this->plural($violations, 'violation', 'violations'),
            $this->plural($failingRules, 'rule', 'rules')
        );
    }

    /**
     * Sorted by the first column, which is `file:line`, so the order is the
     * same on every run. Padded to the widest entry in this group only —
     * one long path elsewhere shouldn't push every other line across.
     *
     * @param list<array{string, string}> $lines
     */
    private function aligned(array $lines): string
    {
        usort($lines, static fn (array $a, array $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

        $width = max(array_map(static fn (array $line) => \strlen($line[0]), $lines));

        return implode("\n", array_map(
            static fn (array $line) => \sprintf('    %s  %s', str_pad($line[0], $width), $line[1]),
            $lines
        ));
    }

    private function plural(int $count, string $singular, string $plural): string
    {
        return \sprintf('%d %s', $count, 1 === $count ? $singular : $plural);
    }
}
