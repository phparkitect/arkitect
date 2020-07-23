<?php

declare(strict_types=1);


namespace ArkitectTests\unit\Specs;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\Specs\HaveNameMatchingSpec;
use PHPUnit\Framework\TestCase;

class HaveNameMatchingSpecTest extends TestCase
{
    public function test_it_should_return_false_if_not_match_name(): void
    {
        $haveNameMatching = new HaveNameMatchingSpec('foo');

        $classDescription = new ClassDescription(
            '/path',
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            []
        );

        $this->assertFalse($haveNameMatching->apply($classDescription));
    }

    public function test_it_should_return_true_if_match_name(): void
    {
        $haveNameMatching = new HaveNameMatchingSpec('HappyIsland');

        $classDescription = new ClassDescription(
            '/path',
            FullyQualifiedClassName::fromString('HappyIsland'),
            [],
            []
        );

        $this->assertTrue($haveNameMatching->apply($classDescription));
    }
}
