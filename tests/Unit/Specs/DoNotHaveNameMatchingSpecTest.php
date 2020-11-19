<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Specs;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Specs\DoNotHaveNameMatchingSpec;
use PHPUnit\Framework\TestCase;

class DoNotHaveNameMatchingSpecTest extends TestCase
{
    public function test_it_should_return_true_if_not_match_name(): void
    {
        $notHaveNameMatching = new DoNotHaveNameMatchingSpec('foo');

        $classDescription = new ClassDescription(
            '/path',
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            []
        );

        $this->assertTrue($notHaveNameMatching->apply($classDescription));
    }

    public function test_it_should_return_false_if_match_name(): void
    {
        $notHaveNameMatching = new DoNotHaveNameMatchingSpec('HappyIsland');

        $classDescription = new ClassDescription(
            '/path',
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            []
        );

        $this->assertFalse($notHaveNameMatching->apply($classDescription));
    }
}
