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
use Arkitect\Command\Check;
use Arkitect\Config;
use Arkitect\FileSystem\FilesystemFileRepository;
use Arkitect\Parser\TargetPhpVersion;
use Arkitect\Report\TextReport;
use Arkitect\Parser\RepositoryParser;

$root = $argv[1] ?? __DIR__;

try {
    $config = Config::create($root)->add([
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

    Rule::allClasses()
        ->that(new Selector\ResideInOneOfTheseNamespaces([
            'Arkitect\Evaluate',
            'Arkitect\Resolve',
            'Arkitect\Report',
            'Arkitect\FileSystem',
        ]))
        ->should(new Constraint\NotDependOnTheseNamespaces(['PhpParser']))
        ->because('only the parser adapter knows which library reads PHP source'),

    Rule::allClasses()
        ->that(new Selector\ResideInNamespace('Arkitect\Evaluate'))
        ->should(new Constraint\NotDependOnTheseNamespaces(['Arkitect\Report']))
        ->because('the rules do not know who will print their results'),]);
    $files = new FilesystemFileRepository($config->root);
} catch (InvalidArgumentException $e) {
    fwrite(\STDERR, $e->getMessage()."\n");
    exit(2);
}

$result = (new Check(new RepositoryParser($files)))->run($config->rules, TargetPhpVersion::create(null));

echo (new TextReport())->render($result), "\n";

exit($result->isClean() ? 0 : 1);
