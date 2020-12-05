<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

interface Parser
{
    public function parse(string $fileContent): void;

    public function onClassAnalyzed(callable $callable): void;
}
