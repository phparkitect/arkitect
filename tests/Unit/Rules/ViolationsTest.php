<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Rules;

use Arkitect\Exceptions\IndexNotFoundException;
use Arkitect\Rules\Violation;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class ViolationsTest extends TestCase
{
    /**
     * @var string
     */
    private $violationData;
    /**
     * @var Violations
     */
    private $violationStore;

    private Violation $violation;

    protected function setUp(): void
    {
        $this->violationData = 'violation';

        $this->violationStore = new Violations();
        $this->violation = Violation::create(
            'App\Controller\ProductController',
            'should implement ContainerInterface',
            1
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
        $violation = Violation::create(
            'App\Controller\Shop',
            'should have name end with Controller',
            2
        );
        $this->violationStore->add($violation);
        $this->assertEquals(2, $this->violationStore->count());
    }

    public function test_to_string(): void
    {
        $violation = Violation::create(
            'App\Controller\Foo',
            'should have name end with Controller',
            3
        );

        $this->violationStore->add($violation);
        $expected = '
App\Controller\ProductController violates rules
should implement ContainerInterface

App\Controller\Foo violates rules
should have name end with Controller
';

        $this->assertEquals($expected, $this->violationStore->toString());
    }
}
