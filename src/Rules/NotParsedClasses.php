<?php
declare(strict_types=1);

namespace Arkitect\Rules;

class NotParsedClasses implements \IteratorAggregate, \Countable
{
    /** @var string[] */
    private $notParsedClasses;

    public function __construct(array $notParsedClasses = [])
    {
        $this->notParsedClasses = $notParsedClasses;
    }

    public function add(string $notParsedClass): void
    {
        $this->notParsedClasses[$notParsedClass] = $notParsedClass;
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->notParsedClasses as $notParsedClass) {
            yield $notParsedClass;
        }
    }

    public function count(): int
    {
        return \count($this->notParsedClasses);
    }

    public function toString(): string
    {
        $errors = '';

        /** @var ParsingError $parsingError */
        foreach ($this->notParsedClasses as $notParsedClass) {
            $errors .= "\nNot Parsed class: ".$notParsedClass;
            $errors .= "\n";
        }

        return $errors;
    }

    public function toArray(): array
    {
        return $this->notParsedClasses;
    }
}
