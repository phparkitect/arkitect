<?php

declare(strict_types=1);

namespace Arkitect\Report;

use Arkitect\CheckResult;
use Arkitect\Evaluate\RuleResults;
use Arkitect\Parser\ParsingErrors;

/**
 * Written for the run that fails; a clean run is one line.
 *
 * `file:line` leads every line because that is the part terminals and IDEs
 * turn into a link, and the order is deterministic because the baseline
 * will depend on it. The rest of the reasoning is in ARCHITECTURE.md.
 */
final class TextReport
{
    public function render(CheckResult $check): string
    {
        $sections = array_filter([
            $this->violations($check->ruleResults),
            $this->unresolved($check->ruleResults),
            $this->uselessRules($check->ruleResults),
            $this->parsingErrors($check->parsingErrors),
            $this->summary($check),
        ], static fn (string $section) => '' !== $section);

        return implode("\n\n", $sections);
    }

    private function violations(RuleResults $results): string
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

    private function unresolved(RuleResults $results): string
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
     */
    private function uselessRules(RuleResults $results): string
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

    private function parsingErrors(ParsingErrors $errors): string
    {
        if (0 === \count($errors)) {
            return '';
        }

        $lines = [];
        foreach ($errors as $error) {
            $lines[] = [$error->filePath, $error->message];
        }

        return \sprintf(
            "! could not parse %s\n%s",
            $this->plural(\count($errors), 'file', 'files'),
            $this->aligned($lines)
        );
    }

    private function summary(CheckResult $check): string
    {
        $violations = 0;
        $failingRules = 0;

        foreach ($check->ruleResults as $result) {
            $found = \count($result->violations);
            $violations += $found;
            $failingRules += $found > 0 ? 1 : 0;
        }

        $counts = \sprintf(
            '%s, %s',
            $this->plural($check->classesChecked, 'class', 'classes'),
            $this->plural(\count($check->ruleResults), 'rule', 'rules')
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
