<?php
declare(strict_types=1);

namespace Arkitect\Tests\E2E;

use Arkitect\Expression\PositiveDescription;
use PHPUnit\Framework\TestCase;

class ExpressionDescriptionTest extends TestCase
{
    public function descriptionProvider(): array
    {
        return [
            ["My class [has|doesn't have] a dependency", 'My class has a dependency', "My class doesn't have a dependency"],
            ['My class has a dependency', 'My class has a dependency', 'My class has a dependency'],
        ];
    }

    /**
     * @dataProvider descriptionProvider
     *
     * @param mixed $msg
     * @param mixed $positive
     * @param mixed $negative
     */
    public function test_should_return_expression_description_in_positive_form($msg, $positive, $negative): void
    {
        $desc = new PositiveDescription($msg);

        $this->assertEquals($positive, $desc->toString());
    }
}
