<?php

declare(strict_types=1);

/**
 * Not the CLI — that doesn't exist yet, and its shape isn't decided (see
 * ARCHITECTURE.md: the domain orchestrator waits until stage 3 evaluate
 * exists and shows what actually needs to flow between stages). This is
 * just the wiring used to run the library by hand while it's being built:
 * a real FileRepository, a real path, a printed summary. Throwaway on
 * purpose — expect to rewrite or delete it.
 *
 * Usage: php run.php [path]
 */

require __DIR__.'/vendor/autoload.php';

use Arkitect\FileSystem\FilesystemFileRepository;
use Arkitect\Parser\TargetPhpVersion;
use Arkitect\ProjectParser;

$path = $argv[1] ?? 'src';

$files = new FilesystemFileRepository($path);
$result = (new ProjectParser($files))->parse(TargetPhpVersion::create(null));

printf("%s: %d classes, %d errors\n", $path, \count($result->classes), \count($result->errors));

foreach ($result->errors as $error) {
    printf("  %s: %s\n", $error->filePath, $error->message);
}
