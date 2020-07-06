<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

interface Parser
{
    public function parse($file): void;
}
