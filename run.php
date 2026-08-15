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
use Arkitect\Evaluate\Selector;
use Arkitect\FileSystem\FilesystemFileRepository;
use Arkitect\Parser\TargetPhpVersion;
use Arkitect\Report\TextReport;
use Arkitect\ProjectParser;
use Arkitect\Resolve\ClassGraph;

$path = $argv[1] ?? 'src';

try {
    $files = new FilesystemFileRepository($path);
} catch (InvalidArgumentException $e) {
    // not swallowing it: a stack trace is the wrong answer to a typo, and the
    // real CLI (stage 4) is where this belongs properly
    fwrite(\STDERR, $e->getMessage()."\n");
    exit(2);
}

$parsed = (new ProjectParser($files))->parse(TargetPhpVersion::create(null));

/**
 * No vendor/ here, so any rule that walks the inheritance chain would hit
 * unresolvable ancestors — see ARCHITECTURE.md, stage 2. The rules below
 * read declarations only, so one parsed set is enough.
 */
$classGraph = new ClassGraph(...$parsed->classes);

$rules = [
    Rule::allClasses()
        ->that(new Selector\ResideInNamespace('Arkitect\Parser'))
        ->should(new Constraint\NotDependOnTheseNamespaces(['Arkitect\Resolve', 'Arkitect\Evaluate']))
        ->because('parsing is the first stage and cannot know what comes after it'),

    Rule::allClasses()
        ->that(new Selector\ResideInNamespace('Arkitect\Resolve'))
        ->should(new Constraint\NotDependOnTheseNamespaces(['Arkitect\Evaluate']))
        ->because('resolving answers questions about types, not about rules'),

    Rule::allClasses()
        ->that(new Selector\ResideInNamespace('Arkitect\Evaluate'))
        ->should(new Constraint\NotDependOnTheseNamespaces(['Arkitect\FileSystem']))
        ->because('reading files is the parser\'s job, and it is already done by now'),

    Rule::allClasses()
        ->that(new Selector\ResideInNamespace('Arkitect\Evaluate\Selector'))
        ->should(new Constraint\NotDependOnTheseNamespaces(['Arkitect\Evaluate\Violation*']))
        ->because('a selector decides what a rule is about and never reports anything'),
];

$results = array_map(
    static fn (Rule $rule) => $rule->check($parsed->classes, $classGraph),
    $rules
);

echo (new TextReport())->render($parsed, $results), "\n";

// unresolved classes and unparsable files fail the run too: in both cases
// something went unchecked, and a green run would say otherwise
$clean = [] === $parsed->errors;

foreach ($results as $result) {
    $clean = $clean && 0 === \count($result->violations) && $result->isConclusive();
}

exit($clean ? 0 : 1);
