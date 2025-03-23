<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Rules;

use Arkitect\Exceptions\IndexNotFoundException;
use Arkitect\Printer\Printer;
use Arkitect\Rules\Violation;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class ViolationsTest extends TestCase
{
    /** @var Violations */
    private $violationStore;

    /** @var Violation */
    private $violation;

    protected function setUp(): void
    {
        $this->violationStore = new Violations();
        $this->violation = new Violation(
            'App\Controller\ProductController',
            'should implement ContainerInterface'
        );
        $this->violationStore->add($this->violation);
    }

    public function test_add_elements_to_store_and_get_it(): void
    {
        $this->assertEquals($this->violation, $this->violationStore->get(0));
    }

    public function test_add_elements_to_store_and_cant_get_it_if_index_not_valid(): void
    {
        $this->expectException(IndexNotFoundException::class);
        $this->expectExceptionMessage('Index not found 1111');
        $this->assertEquals('', $this->violationStore->get(1111));
    }

    public function test_count(): void
    {
        $violation = new Violation(
            'App\Controller\Shop',
            'should have name end with Controller'
        );
        $this->violationStore->add($violation);
        $this->assertEquals(2, $this->violationStore->count());
    }

    public function test_to_string(): void
    {
        $violation = new Violation(
            'App\Controller\Foo',
            'should have name end with Controller'
        );

        $this->violationStore->add($violation);
        $expected = '
App\Controller\ProductController has 1 violations
  should implement ContainerInterface

App\Controller\Foo has 1 violations
  should have name end with Controller
';

        $this->assertEquals($expected, $this->violationStore->toString(Printer::FORMAT_TEXT));
    }

    public function test_get_iterable(): void
    {
        $violation = new Violation(
            'App\Controller\Shop',
            'should have name end with Controller'
        );
        $this->violationStore->add($violation);
        $iterable = $this->violationStore->getIterator();

        $this->assertEquals([
            $this->violation,
            $violation,
        ], iterator_to_array($iterable));
    }

    public function test_get_array(): void
    {
        $violation1 = new Violation(
            'App\Controller\Shop',
            'should have name end with Controller'
        );
        $violation2 = new Violation(
            'App\Controller\Shop',
            'should implement AbstractController'
        );
        $this->violationStore->add($violation1);
        $this->violationStore->add($violation2);

        $this->assertEquals([
            $this->violation,
            $violation1,
            $violation2,
        ], $this->violationStore->toArray());
    }

    public function test_remove_violations_from_violations(): void
    {
        $violation1 = new Violation(
            'App\Controller\Shop',
            'should have name end with Controller'
        );
        $this->violationStore->add($violation1);

        $violation2 = new Violation(
            'App\Controller\Shop',
            'should implement AbstractController'
        );
        $this->violationStore->add($violation2);

        $this->assertCount(3, $this->violationStore->toArray());

        $violationsBaseline = new Violations();
        $violationsBaseline->add($this->violation);

        $this->violationStore->remove($violationsBaseline);

        $this->assertCount(2, $this->violationStore->toArray());
        $this->assertEquals([
            $violation1,
            $violation2,
        ], $this->violationStore->toArray());
    }

    public function test_sort(): void
    {
        $violationStore = new Violations();
        $violation1 = new Violation(
            'App\Controller\Shop',
            'AAA',
            20
        );
        $violation2 = new Violation(
            'App\Controller\Shop',
            'BBB',
            10
        );
        $violation3 = new Violation(
            'App\Controller\Shop',
            'AAA',
            10
        );
        $violation4 = new Violation(
            'App\Controller\Abc',
            'CCC',
            30
        );
        $violationStore->add($violation1);
        $violationStore->add($violation2);
        $violationStore->add($violation3);
        $violationStore->add($violation4);

        $this->assertEquals([
            $violation1,
            $violation2,
            $violation3,
            $violation4,
        ], $violationStore->toArray());

        $violationStore->sort();

        $this->assertSame([
            $violation4, // fqcn is most important
            $violation3, // then line number
            $violation2, // then error message
            $violation1,
        ], $violationStore->toArray());
    }

    public function test_remove_violations_from_violations_ignore_linenumber(): void
    {
        $violation1 = new Violation(
            'App\Controller\Shop',
            'should have name end with Controller',
            42
        );
        $this->violationStore->add($violation1);

        $violation2 = new Violation(
            'App\Controller\Shop',
            'should implement AbstractController',
            21
        );
        $this->violationStore->add($violation2);

        $violation3 = new Violation(
            'App\Controller\Shop',
            'should have name end with Controller',
            5
        );
        $this->violationStore->add($violation3);

        $this->assertCount(4, $this->violationStore->toArray());

        $violationsBaseline = new Violations();
        $violationsBaseline->add(new Violation(
            'App\Controller\Shop',
            'should have name end with Controller',
            21
        ));

        $this->violationStore->remove($violationsBaseline, true);

        $this->assertCount(3, $this->violationStore->toArray());
        $this->assertEquals([
            $this->violation,
            $violation2,
            $violation3,
        ], $this->violationStore->toArray());
    }
}
