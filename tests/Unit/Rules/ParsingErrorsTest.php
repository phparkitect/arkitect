<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Rules;

use Arkitect\Exceptions\IndexNotFoundException;
use Arkitect\Rules\ParsingError;
use Arkitect\Rules\ParsingErrors;
use PHPUnit\Framework\TestCase;

class ParsingErrorsTest extends TestCase
{
    /** @var ParsingErrors */
    private $parsingStore;

    /** @var ParsingError */
    private $parsingError;

    protected function setUp(): void
    {
        $this->parsingStore = new ParsingErrors();
        $this->parsingError = ParsingError::create(
            'App\Controller\ProductController',
            'Syntax error, unexpected T_STRING on line 8',
            1
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
            2
        );
        $this->parsingStore->add($parsingError);
        self::assertEquals(2, $this->parsingStore->count());
    }

    public function test_to_string(): void
    {
        $parsingError = ParsingError::create(
            'App\Controller\Foo',
            'Syntax error, unexpected T_STRING on line 8'
        );

        $this->parsingStore->add($parsingError);
        $expected = '
Syntax error, unexpected T_STRING on line 8 in file: App\Controller\ProductController

Syntax error, unexpected T_STRING on line 8 in file: App\Controller\Foo
';

        self::assertEquals($expected, $this->parsingStore->toString());
    }

    public function test_calling_iterator(): void
    {
        self::assertInstanceOf(\Generator::class, $this->parsingStore->getIterator());
    }

    public function test_convert_to_array(): void
    {
        $expected = [
            $this->parsingError,
        ];

        self::assertEquals($expected, $this->parsingStore->toArray());
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
