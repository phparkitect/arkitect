<?php

namespace Arkitect\Analyzer;

interface Parser
{
    public function parse($file): void;
}