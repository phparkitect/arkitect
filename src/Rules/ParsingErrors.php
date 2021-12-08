<?php
declare(strict_types=1);

namespace Arkitect\Rules;

use Arkitect\Exceptions\IndexNotFoundException;

class ParsingErrors implements \IteratorAggregate, \Countable
{
    /**
     * @var ParsingError[]
     */
    private $parsingErrors;

    public function __construct(array $parsingErrors = [])
    {
        $this->parsingErrors = $parsingErrors;
    }

    public function add(ParsingError $parsingError): void
    {
        $this->parsingErrors[] = $parsingError;
    }

    public function get(int $index): ParsingError
    {
        if (!\array_key_exists($index, $this->parsingErrors)) {
            throw new IndexNotFoundException($index);
        }

        return $this->parsingErrors[$index];
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->parsingErrors as $parsingError) {
            yield $parsingError;
        }
    }

    public function count(): int
    {
        return \count($this->parsingErrors);
    }

    public function toString(): string
    {
        $errors = '';

        /** @var ParsingError $parsingError */
        foreach ($this->parsingErrors as $parsingError) {
            $errors .= "\n".$parsingError->getError().' in file: '.$parsingError->getRelativeFilePath();
            $errors .= "\n";
        }

        return $errors;
    }

    public function toArray(): array
    {
        return $this->parsingErrors;
    }
}
