<?php declare(strict_types=1);

namespace ArkitectTests;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FileParser;
use Arkitect\Testing\EventDispatcherSpy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;

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

        $fp->parse(new FakeFile('my/file/path', $code));

        [$firstEvent, $secondEvent] = $ed->getDispatchedEvents();

        $this->assertInstanceOf(ClassDescription::class, $firstEvent->getClassDescription());
        $this->assertInstanceOf(ClassDescription::class, $secondEvent->getClassDescription());
    }
}

class FakeFile {

    private $path;
    private $content;

    public function __construct($path, $content)
    {
        $this->path = $path;
        $this->content = $content;
    }

    public function getRelativePath()
    {
        return $this->path;
    }

    public function getContents()
    {
        return $this->content;
    }

}