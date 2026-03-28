<?php

declare(strict_types=1);

namespace Arkitect\Analyzer;

use Arkitect\Exceptions\IndexNotFoundException;

/**
 * @template-implements \IteratorAggregate<ParsingError>
 * @template-implements \ArrayAccess<int, ParsingError>
 */
class ParsingErrors implements \IteratorAggregate, \Countable, \ArrayAccess
{
    /**
     * @var array<ParsingError>
     */
    private array $parsingErrors;

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

    public function merge(self $other): void
    {
        $this->parsingErrors = array_merge($this->parsingErrors, $other->parsingErrors);
    }

    /** @param int $offset */
    public function offsetExists($offset): bool
    {
        return \array_key_exists($offset, $this->parsingErrors);
    }

    /**
     * @param int $offset
     *
     * @return ParsingError
     */
    public function offsetGet($offset): mixed
    {
        return $this->parsingErrors[$offset];
    }

    /**
     * @param int|null     $offset
     * @param ParsingError $value
     */
    public function offsetSet($offset, $value): void
    {
        if (null === $offset) {
            $this->parsingErrors[] = $value;
        } else {
            $this->parsingErrors[$offset] = $value;
        }
    }

    /** @param int $offset */
    public function offsetUnset($offset): void
    {
        unset($this->parsingErrors[$offset]);
    }
}
