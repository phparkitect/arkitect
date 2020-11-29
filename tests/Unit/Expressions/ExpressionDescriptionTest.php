<?php
declare(strict_types=1);

namespace Arkitect\Tests\E2E;

use Arkitect\ClassSet;
use Arkitect\Expression\PositiveExpressionDescription;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\ResideInNamespace;
use Arkitect\PHPUnit\ArchRuleTestCase;
use Arkitect\Rules\Rule;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class ExpressionDescriptionTest extends TestCase
{

    public function descriptionProvider(): array
    {
        return [
            ["My class [has|doesn't have] a dependency", "My class has a dependency", "My class doesn't have a dependency"],
            ["My class has a dependency", "My class has a dependency", "My class has a dependency"]
        ];
    }

    /**
     * @dataProvider descriptionProvider
     */
    public function test_should_return_expression_description_in_positive_form($msg, $positive, $negative)
    {
        $desc = new PositiveExpressionDescription($msg);

        $this->assertEquals($positive, $desc->toString());

    }
}
