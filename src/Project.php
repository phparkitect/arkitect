<?php

declare(strict_types=1);

namespace Arkitect;

use Arkitect\FileSystem\FilesystemFileRepository;
use Arkitect\Parser\ParseResult;
use Arkitect\Parser\ProjectParser;
use Arkitect\Parser\TargetPhpVersion;

/**
 * First-draft composition root: wires the real filesystem adapter to
 * ProjectParser for a given root path. Stops at stage 1 on purpose — see
 * ARCHITECTURE.md, "domain orchestrator" is deferred until stage 3
 * (Evaluate) exists and shows what actually needs to flow between stages.
 */
final class Project
{
    public static function parse(string $rootPath, TargetPhpVersion $targetPhpVersion): ParseResult
    {
        $files = new FilesystemFileRepository($rootPath);

        return (new ProjectParser($files))->parse($targetPhpVersion);
    }
}
