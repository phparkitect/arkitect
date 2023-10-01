<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions;

use Arkitect\Expression\Description;
use Arkitect\Rules\ViolationMessage;
use PHPUnit\Framework\TestCase;

class ViolationDescriptionTest extends TestCase
{
    public function test_it_should_just_return_expression_description_when_self_explanatory(): void
    {
        $expressionDescription = new Description('should not', 'just cause');
        $description = ViolationMessage::selfExplanatory($expressionDescription);
        self::assertEquals($expressionDescription->toString(), $description->toString());
    }

    public function test_it_should_prepend_description_when_present(): void
    {
        $expressionDescription = new Description('should not', 'reasons');
        $description = ViolationMessage::withDescription($expressionDescription, 'it does something');
        self::assertEquals(
            "it does something\n"
            ."from the rule\n"
            ."should not\n"
            .'because reasons',
            $description->toString()
        );
    }
}
