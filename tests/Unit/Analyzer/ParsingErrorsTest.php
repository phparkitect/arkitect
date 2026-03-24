<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Rules;

use Arkitect\Analyzer\ParsingError;
use Arkitect\Analyzer\ParsingErrors;
use Arkitect\Exceptions\IndexNotFoundException;
use PHPUnit\Framework\TestCase;

class ParsingErrorsTest extends TestCase
{
    private ParsingErrors $parsingStore;

    private ParsingError $parsingError;

    protected function setUp(): void
    {
        $this->parsingStore = new ParsingErrors();
        $this->parsingError = ParsingError::create(
            'App\Controller\ProductController',
            'Syntax error, unexpected T_STRING on line 8',
        );
        $this->parsingStore->add($this->parsingError);
    }

    public function test_add_elements_to_store_and_get_it(): void
    {
        self::assertEquals($this->parsingError, $this->parsingStore->get(0));
    }

    public function test_add_elements_to_store_and_cant_get_it_if_index_not_valid(): void
    {
        $this->expectException(IndexNotFoundException::class);
        $this->expectExceptionMessage('Index not found 1111');

        self::assertEquals('', $this->parsingStore->get(1111));
    }

    public function test_count(): void
    {
        $parsingError = ParsingError::create(
            'App\Controller\Shop',
            'Syntax error, unexpected T_STRING on line 8',
        );

        $this->parsingStore->add($parsingError);

        self::assertEquals(2, $this->parsingStore->count());
    }

    public function test_calling_iterator(): void
    {
        self::assertInstanceOf(\Generator::class, $this->parsingStore->getIterator());
    }

    public function test_get_iterable(): void
    {
        $parsingError = ParsingError::create(
            'App\Controller\Foo',
            'Syntax error, unexpected T_STRING on line 8'
        );

        $this->parsingStore->add($parsingError);

        $iterable = $this->parsingStore->getIterator();

        self::assertEquals([$this->parsingError, $parsingError], iterator_to_array($iterable));
    }
}
