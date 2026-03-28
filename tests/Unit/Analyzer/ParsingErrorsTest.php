<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

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

    public function test_offset_exists_returns_true_for_existing_index(): void
    {
        self::assertTrue(isset($this->parsingStore[0]));
    }

    public function test_offset_exists_returns_false_for_missing_index(): void
    {
        self::assertFalse(isset($this->parsingStore[99]));
    }

    public function test_offset_get_returns_element_at_index(): void
    {
        self::assertSame($this->parsingError, $this->parsingStore[0]);
    }

    public function test_offset_set_appends_when_offset_is_null(): void
    {
        $second = ParsingError::create('App\Controller\Bar', 'Syntax error on line 5');
        $this->parsingStore[] = $second;

        self::assertCount(2, $this->parsingStore);
        self::assertSame($second, $this->parsingStore[1]);
    }

    public function test_offset_set_assigns_to_specific_index(): void
    {
        $replacement = ParsingError::create('App\Controller\Baz', 'Syntax error on line 3');
        $this->parsingStore[0] = $replacement;

        self::assertSame($replacement, $this->parsingStore[0]);
    }

    public function test_offset_unset_removes_element(): void
    {
        unset($this->parsingStore[0]);

        self::assertFalse(isset($this->parsingStore[0]));
        self::assertCount(0, $this->parsingStore);
    }
}
