<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Rules;

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

    protected function setUp(): void
    {
        $this->violationData = 'violation';

        $this->violationStore = new Violations();
        $this->violationStore->add($this->violationData);
    }

    public function testAddElementsToStoreAndGetIt(): void
    {
        $this->assertEquals($this->violationData, $this->violationStore->get(0));
    }

    public function testAddElementsToStoreAndCantGetItIfIndexNotValid(): void
    {
        $this->assertEquals('', $this->violationStore->get(1111));
    }

    public function testCount(): void
    {
        $this->violationStore->add('foo');
        $this->assertEquals(2, $this->violationStore->count());
    }

    public function testToString(): void
    {
        $this->violationStore->add('foo');
        $expected = 'violation
foo';

        $this->assertEquals($expected, $this->violationStore->toString());
    }
}
