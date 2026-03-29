<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

class ParsedFiles
{
    private ClassDescriptions $classDescriptions;

    private ParsingErrors $parsingErrors;

    public function __construct()
    {
        $this->classDescriptions = new ClassDescriptions();
        $this->parsingErrors = new ParsingErrors();
    }

    public function add(ParserResult $result): void
    {
        foreach ($result->classDescriptions() as $classDescription) {
            $this->classDescriptions[] = $classDescription;
        }

        $this->parsingErrors->merge($result->parsingErrors());
    }

    public function classDescriptions(): ClassDescriptions
    {
        return $this->classDescriptions;
    }

    public function parsingErrors(): ParsingErrors
    {
        return $this->parsingErrors;
    }
}
