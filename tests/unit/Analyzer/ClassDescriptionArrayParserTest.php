<?php

declare(strict_types=1);

namespace ArkitectTests\Analyzer;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\ClassDescriptionArrayParser;
use Arkitect\Testing\EventDispatcherSpy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class ClassDescriptionArrayParserTest extends TestCase
{
   public function test()
   {
       $eventiDispatcher = $this->prophesize(EventDispatcherSpy::class);
       $eventiDispatcher->dispatch(Argument::any())->shouldBeCalled();

       $classDescriptionArrayParser = new ClassDescriptionArrayParser($eventiDispatcher->reveal());

       $classDescription = ClassDescription::build('FQCN', 'fullyPath')->get();
       $classDescriptionArrayParser->parse($classDescription);
   }
}
