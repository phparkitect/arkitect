<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions;

use Arkitect\Expression\NegativeDescription;
use Arkitect\Expression\PositiveDescription;
use PHPUnit\Framework\TestCase;

class NegativeDescriptionTest extends TestCase
{
    public function test_it_should_create_negative_description(): void
    {
        $description = new PositiveDescription('should [depend|not depend] only on classes: Domain', 'we want to add this rule');

        $negativeDescription = new NegativeDescription($description);
        $this->assertEquals($description->getPattern(), $negativeDescription->getPattern());
        $this->assertEquals('should not depend only on classes: Domain because we want to add this rule', $negativeDescription->toString());
    }
}
