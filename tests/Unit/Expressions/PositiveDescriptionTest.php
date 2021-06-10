<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions;

use Arkitect\Expression\PositiveDescription;
use PHPUnit\Framework\TestCase;

class PositiveDescriptionTest extends TestCase
{
    public function test_it_should_create_positive_description(): void
    {
        $description = 'should [depend|not depend] only on classes: Domain';

        $positiveDescription = new PositiveDescription($description);
        $this->assertEquals($description, $positiveDescription->getPattern());
        $this->assertEquals('should depend only on classes: Domain', $positiveDescription->toString());
    }
}
