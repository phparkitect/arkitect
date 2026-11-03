<?php

declare(strict_types=1);

namespace Arkitect\Report;

use Arkitect\Command\CheckResult;
use Arkitect\Evaluate\UnresolvedClass;
use Arkitect\Evaluate\Violation;
use Arkitect\Parser\ParsingError;

/**
 * GitLab's Code Quality report: one JSON array, the CodeClimate format
 * reduced to the fields GitLab reads.
 *
 * Named for GitLab and not `json` because it is not a general
 * machine-readable format: GitHub reads SARIF for code scanning and
 * workflow commands for inline annotations, and the tools that serve both
 * ship two formats rather than one. A name promising more than the format
 * delivers would send someone on GitHub away with a file nothing reads.
 *
 * Every entry is something that failed the run, not only violations: a
 * parsing error or a class that could not be resolved means part of the
 * tree went unlooked-at, and leaving them out would show a clean merge
 * request for a run that exited non-zero.
 */
final class GitlabReport implements Report
{
    /**
     * One severity for everything. The tool has no scale of its own —
     * `isClean()` is the only judgement it makes — and inventing one here
     * would put a number on findings nobody ranked.
     */
    private const SEVERITY = 'major';

    public function render(CheckResult $check): string
    {
        $entries = [];

        foreach ($check->ruleResults as $result) {
            foreach ($result->violations as $violation) {
                $entries[] = $this->fromViolation($violation);
            }

            foreach ($result->unresolved as $unresolved) {
                $entries[] = $this->fromUnresolved($unresolved);
            }
        }

        foreach ($check->parsingErrors as $error) {
            $entries[] = $this->fromParsingError($error);
        }

        // slashes unescaped so a path reads as a path in the artifact
        return json_encode($entries, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR)."\n";
    }

    /** @return array<string, mixed> */
    private function fromViolation(Violation $violation): array
    {
        return $this->entry(
            $violation->detail,
            $violation->constraint,
            $violation->identity(),
            $violation->filePath,
            $violation->line,
        );
    }

    /**
     * The same class left unresolved by several rules is one finding, which
     * the identity says and GitLab then shows once.
     *
     * @return array<string, mixed>
     */
    private function fromUnresolved(UnresolvedClass $unresolved): array
    {
        return $this->entry(
            // one entry is read on its own, so it carries the reason the
            // text report says once at the top
            $unresolved->detail.': '.UnresolvedClass::REASON,
            UnresolvedClass::class,
            $unresolved->fqcn,
            $unresolved->filePath,
            $unresolved->line,
        );
    }

    /** @return array<string, mixed> */
    private function fromParsingError(ParsingError $error): array
    {
        return $this->entry(
            $error->message,
            ParsingError::class,
            $error->filePath,
            $error->filePath,
            1,
        );
    }

    /**
     * `check_name` names the check, never the finding, which is what makes
     * it the same string across every occurrence. `fingerprint` is what
     * GitLab groups by, so it carries the identity rather than the prose.
     *
     * @return array<string, mixed>
     */
    private function entry(string $description, string $checkName, string $identity, string $path, int $line): array
    {
        return [
            'description' => $description,
            'check_name' => $checkName,
            'fingerprint' => hash('sha256', $checkName."\0".$identity),
            'severity' => self::SEVERITY,
            'location' => [
                'path' => $path,
                'lines' => ['begin' => $line],
            ],
        ];
    }
}
