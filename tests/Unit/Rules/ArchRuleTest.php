<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Rules;

use Arkitect\Rules\ArchRuleGivenClasses;
use Arkitect\Rules\Rule;
use PHPUnit\Framework\TestCase;

class ArchRuleTest extends TestCase
{
    public function test_it_should_create_arch_rule(): void
    {
        $this->assertInstanceOf(ArchRuleGivenClasses::class, Rule::classes());
    }
}
