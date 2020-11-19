<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\ClassDescriptionArrayParser;
use Arkitect\Testing\EventDispatcherSpy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class ClassDescriptionArrayParserTest extends TestCase
{
    public function test_event_dispatch_is_called_when_class_description_array_is_parsed(): void
    {
        $eventiDispatcher = $this->prophesize(EventDispatcherSpy::class);
        $eventiDispatcher->dispatch(Argument::any())->shouldBeCalled();

        $classDescriptionArrayParser = new ClassDescriptionArrayParser($eventiDispatcher->reveal());

        $classDescription = ClassDescription::build('FQCN', 'fullyPath')->get();
        $classDescriptionArrayParser->parse($classDescription);
    }
}
