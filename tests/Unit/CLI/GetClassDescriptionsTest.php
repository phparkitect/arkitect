<?php

declare(strict_types=1);

namespace Tests\Unit\CLI;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\ClassSetRules;
use Arkitect\CLI\GetClassDescriptions;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Rules\FakeClassSet;
use Tests\Unit\Rules\FakeParser;
use Tests\Unit\Rules\FakeRule;

class GetClassDescriptionsTest extends TestCase
{
    public function test_it_should_get_class_descriptions(): void
    {
        $rule = new FakeRule();

        $classSetRule = ClassSetRules::create(new FakeClassSet(), ...[$rule]);
        $parser = new FakeParser();
        $getClassDescriptions = GetClassDescriptions::execute($classSetRule, $parser);

        $this->assertEquals([
            'uno' => new ClassDescription(FullyQualifiedClassName::fromString('uno'), [], [], null),
        ], $getClassDescriptions);
    }
}
