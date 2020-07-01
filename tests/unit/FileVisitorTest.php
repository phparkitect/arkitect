<?php declare(strict_types=1);

namespace ArkitectTests;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FileParser;
use Arkitect\Testing\EventDispatcherSpy;
use PHPUnit\Framework\TestCase;

class FileVisitorTest extends TestCase
{
    public function test_should_create_a_class_description()
    {
        $ed = new EventDispatcherSpy();

        $fp = new FileParser($ed);

        $code = <<< 'EOF'
<?php

namespace Root\Namespace1;

use Root\Namespace2\D;

class Dog implements AnInterface, InterfaceTwo
{   
} 

class Cat implements AnInterface
{

}
EOF;

        $fp->parse('my/file/path', $code);

        [$firstEvent, $secondEvent] = $ed->getDispatchedEvents();

        $this->assertInstanceOf(ClassDescription::class, $firstEvent->getClassDescription());
        $this->assertInstanceOf(ClassDescription::class, $secondEvent->getClassDescription());
    }
}