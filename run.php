<?php

declare(strict_types=1);

/**
 * Not the CLI — that doesn't exist yet, and its shape isn't decided (see
 * ARCHITECTURE.md: Config and Report are stage 4). This is just the wiring
 * used to run the library by hand while it's being built: a real
 * FileRepository, a real path, real rules, a printed summary. Throwaway on
 * purpose — expect to rewrite or delete it.
 *
 * The rules below are this codebase's own stage ordering, so running it
 * against src/ is a real check and not a demo: parsing knows nothing about
 * resolving or evaluating, and resolving knows nothing about evaluating.
 *
 * Usage: php run.php [path]
 */

require __DIR__.'/vendor/autoload.php';

use Arkitect\Evaluate\Constraint;
use Arkitect\Evaluate\Rule;
use Arkitect\Evaluate\RuleResult;
use Arkitect\Evaluate\Selector;
use Arkitect\FileSystem\FilesystemFileRepository;
use Arkitect\Parser\TargetPhpVersion;
use Arkitect\ProjectParser;
use Arkitect\Resolve\ClassGraph;

$path = $argv[1] ?? 'src';

$files = new FilesystemFileRepository($path);
$parsed = (new ProjectParser($files))->parse(TargetPhpVersion::create(null));

printf("%s: %d classes, %d errors\n", $path, \count($parsed->classes), \count($parsed->errors));

foreach ($parsed->errors as $error) {
    printf("  %s: %s\n", $error->filePath, $error->message);
}

/**
 * No vendor/ here, so any rule that walks the inheritance chain would hit
 * unresolvable ancestors — see ARCHITECTURE.md, stage 2. The rules below
 * read declarations only, so one parsed set is enough.
 */
$classGraph = new ClassGraph(...$parsed->classes);

$rules = [
    'parsing depends on neither resolving nor evaluating' => new Rule(
        [new Selector\ResideInNamespace('Arkitect\Parser')],
        [new Constraint\NotDependOnTheseNamespaces(['Arkitect\Resolve', 'Arkitect\Evaluate'])]
    ),
    'resolving does not depend on evaluating' => new Rule(
        [new Selector\ResideInNamespace('Arkitect\Resolve')],
        [new Constraint\NotDependOnTheseNamespaces(['Arkitect\Evaluate'])]
    ),
    'nothing outside the filesystem component touches it directly' => new Rule(
        [new Selector\ResideInNamespace('Arkitect\Evaluate')],
        [new Constraint\NotDependOnTheseNamespaces(['Arkitect\FileSystem'])]
    ),
    'selectors do not know that violations exist' => new Rule(
        [new Selector\ResideInNamespace('Arkitect\Evaluate\Selector')],
        [new Constraint\NotDependOnTheseNamespaces(['Arkitect\Evaluate\Violation*'])]
    ),
];

$failed = false;

foreach ($rules as $label => $rule) {
    $result = $rule->check($parsed->classes, $classGraph);
    $failed = $failed || \count($result->violations) > 0 || !$result->isConclusive();

    printf("\n%s\n  %s\n", $label, summarize($result));

    foreach ($result->violations as $violation) {
        printf("  %s:%d %s %s\n", $violation->filePath, $violation->line, $violation->fqcn, $violation->detail);
    }

    // kept apart from the violations above on purpose: these are classes the
    // run could not decide about, which is a gap in what we parsed rather
    // than something wrong with the code
    foreach ($result->unresolved as $unresolved) {
        printf("  ? %s:%d %s %s\n", $unresolved->filePath, $unresolved->line, $unresolved->fqcn, $unresolved->detail);
    }
}

exit($failed ? 1 : 0);

function summarize(RuleResult $result): string
{
    if ($result->matchedNothing()) {
        return 'matched no classes — the rule checked nothing at all';
    }

    $summary = \sprintf('%d classes checked, %d violations', $result->checked, \count($result->violations));

    if (!$result->isConclusive()) {
        $summary .= \sprintf(', %d unresolved', \count($result->unresolved));
    }

    return $summary;
}
