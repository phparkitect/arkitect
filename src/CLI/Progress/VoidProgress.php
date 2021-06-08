<?php
declare(strict_types=1);

namespace Arkitect\CLI\Progress;

use Arkitect\ClassSet;

class VoidProgress implements Progress
{
    public function startFileSetAnalysis(ClassSet $set): void
    {
    }

    public function startParsingFile(string $file): void
    {
    }

    public function endParsingFile(string $file): void
    {
    }

    public function endFileSetAnalysis(ClassSet $set): void
    {
    }
}
