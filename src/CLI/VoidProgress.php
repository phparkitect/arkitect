<?php

namespace Arkitect\CLI;

use Arkitect\ClassSet;

class VoidProgress implements Progress
{
    public function startFileSetAnalysis(ClassSet $set)
    {
    }

    public function startParsingFile(string $file)
    {
    }

    public function endParsingFile(string $file)
    {
    }

    public function endFileSetAnalysis(ClassSet $set)
    {
    }
}