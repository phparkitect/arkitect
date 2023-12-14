<?php
declare(strict_types=1);

namespace Arkitect\Analyzer;

use Arkitect\Exceptions\ClassFileNotFoundException;

class ClassDependency
{
    /** @var int */
    private $line;

    /** @var \Arkitect\Analyzer\FullyQualifiedClassName */
    private $FQCN;

    public function __construct(string $FQCN, int $line)
    {
        $this->line = $line;
        $this->FQCN = FullyQualifiedClassName::fromString($FQCN);
    }

    public function matches(string $pattern): bool
    {
        return $this->FQCN->matches($pattern);
    }

    public function matchesOneOf(string ...$patterns): bool
    {
        foreach ($patterns as $pattern) {
            if ($this->FQCN->matches($pattern)) {
                return true;
            }
        }

        return false;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function getFQCN(): FullyQualifiedClassName
    {
        return $this->FQCN;
    }

    /**
     * @throws ClassFileNotFoundException
     * @throws \ReflectionException
     */
    public function getClassDescription(): ClassDescription
    {
        /** @var class-string $dependencyFqcn */
        $dependencyFqcn = $this->getFQCN()->toString();
        $reflector = new \ReflectionClass($dependencyFqcn);
        $filename = $reflector->getFileName();
        if (false === $filename) {
            throw new ClassFileNotFoundException($dependencyFqcn);
        }

        $fileParser = FileParserFactory::createFileParser();
        $fileParser->parse(file_get_contents($filename), $filename);
        $classDescriptionList = $fileParser->getClassDescriptions();

        return array_pop($classDescriptionList);
    }
}
