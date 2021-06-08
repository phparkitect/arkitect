<?php
declare(strict_types=1);

namespace Arkitect\CLI\Progress;

use Arkitect\ClassSet;

interface Progress
{
    public function startFileSetAnalysis(ClassSet $set);

    public function startParsingFile(string $file);

    public function endParsingFile(string $file);

    public function endFileSetAnalysis(ClassSet $set);
}
