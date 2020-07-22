<?php

declare(strict_types=1);


namespace ArkitectTests\unit\Rules;

use Arkitect\Rules\ArchRule;
use Arkitect\Rules\ArchRuleGivenClasses;
use PHPUnit\Framework\TestCase;

class ArchRuleTest extends TestCase
{
    public function test_it_should_create_arch_rule(): void
    {
        $this->assertInstanceOf(ArchRuleGivenClasses::class, ArchRule::classes());
    }
}
