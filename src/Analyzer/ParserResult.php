<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

class ParserResult
{
    private ClassDescriptions $classDescriptions;

    private ParsingErrors $parsingErrors;

    private function __construct(ClassDescriptions $classDescriptions, ParsingErrors $parsingErrors)
    {
        $this->classDescriptions = $classDescriptions;
        $this->parsingErrors = $parsingErrors;
    }

    public static function create(ClassDescriptions $classDescriptions, ParsingErrors $parsingErrors): self
    {
        return new self($classDescriptions, $parsingErrors);
    }

    public static function withClassDescriptions(ClassDescriptions $classDescriptions): self
    {
        return new self($classDescriptions, new ParsingErrors());
    }

    public static function withParsingErrors(ParsingErrors $parsingErrors): self
    {
        return new self(new ClassDescriptions(), $parsingErrors);
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
