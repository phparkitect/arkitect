<?php

declare(strict_types=1);


namespace ArkitectTests\unit\Rules;

use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class ViolationStoreTest extends TestCase
{
    /**
     * @var string
     */
    private $violationData;
    /**
     * @var Violations
     */
    private $violationStore;

    public function setUp(): void
    {
        $this->violationData = 'violation';

        $this->violationStore = new Violations();
        $this->violationStore->add($this->violationData);
    }

    public function test_add_elements_to_store_and_get_it(): void
    {
        $this->assertEquals($this->violationData, $this->violationStore->get(0));
    }

    public function test_add_elements_to_store_and_cant_get_it_if_index_not_valid(): void
    {
        $this->assertEquals('', $this->violationStore->get(1111));
    }

    public function test_count(): void
    {
        $this->violationStore->add('foo');
        $this->assertEquals(2, $this->violationStore->count());
    }

    public function test_to_string(): void
    {
        $this->violationStore->add('foo');
        $expected = 'violation
foo';

        $this->assertEquals($expected, $this->violationStore->toString());
    }
}
