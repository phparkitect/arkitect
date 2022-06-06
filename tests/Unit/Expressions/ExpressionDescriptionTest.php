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
            ['My class [have|not have] a dependency', 'My class have a dependency because we want to add this rule', 'My class not have a dependency because we want to add this rule'],
            ['My class have a dependency', 'My class have a dependency because we want to add this rule', 'My class have a dependency because we want to add this rule'],
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
        $desc = new PositiveDescription($msg, 'we want to add this rule');

        $this->assertEquals($positive, $desc->toString());
    }
}
