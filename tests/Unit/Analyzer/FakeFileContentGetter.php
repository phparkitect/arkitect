<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\FileContentGetterInterface;
use Arkitect\Rules\NotParsedClasses;
use Arkitect\Rules\ParsingError;

class FakeFileContentGetter implements FileContentGetterInterface
{
    /** @var string */
    private $classFQCN;

    public function open(string $classFQCN): void
    {
        $this->classFQCN = $classFQCN;
    }

    public function getContent(): ?string
    {
        switch ($this->classFQCN) {
            case 'Root\Animals\Tiger':
                return '<?php

namespace Root\Animals;

class Tiger extends Feline
{
    public function foo()
    {
       self::bar();
       static::bar();
       parent::baz();
    }
    public static function bar()
    {
    }
    public function doSomething(self $self, static $static)
    {
    }
}';
            case 'Foo\Bar\BrokenClass':
                return '<?php

namespace Foo\Bar;

class BrokenClass
{
    public function __construct()
    {
       FOO
    }

';
            case 'Foo\Bar\MyClassShouldReturnAllDependencies':
                return '<?php
namespace Foo\Bar;

use Doctrine\MongoDB\Collection;
use Foo\Baz\Baz;
use Symfony\Component\HttpFoundation\Request;
use Foo\Baz\StaticClass;

class MyClassShouldReturnAllDependencies implements Baz
{
    public function __construct(Request $request)
    {
        $collection = new Collection($request);
        $static = StaticClass::foo();
    }
}
';
            case 'Foo\Bar\MyClass':
                return '<?php
namespace Foo\Bar;

use Symfony\Component\HttpFoundation\Request;

class MyClass
{
    public function __construct(Request $request)
    {
    }
}
';
            case 'Root\Animals\Animal':
                return '<?php

namespace Root\Animals;

class Animal
{
    public function __construct()
    {
        $y = 1;
        $fn1 = fn($x) => $x + $y;
    }
}
';
            case 'Root\Namespace2\Dog':
                return '<?php

namespace Root\Namespace1;

use Root\Namespace2;

class Dog implements AnInterface, InterfaceTwo
{
    public function foo()
    {
        $projector2 = new class() implements Another\ForbiddenInterface
            {
                public function applyDummyDomainEvent(int $anInteger): void
                {
                }

                public function getEventsTypes(): string
                {
                    return "";
                }
            };
    }
}
';
            case 'Root\Animals\Cat':
                return '<?php

namespace Root\Animals;

class Feline
{
}

class Cat extends Feline
{

}';
            case 'Symfony\Component\HttpFoundation\Request':
                return $this->simpleClass('Symfony\Component\HttpFoundation\Request', 'Request');
            case 'Another\ForbiddenInterface':
                return $this->simpleClass('Another\ForbiddenInterface', 'ForbiddenInterface');
            case 'Root\Namespace2\AnInterface':
                return $this->simpleClass('Root\Namespace2\AnInterface', 'AnInterface');
            case 'Root\Namespace2\InterfaceTwo':
                return $this->simpleClass('Root\Namespace2\InterfaceTwo', 'InterfaceTwo');
            case 'Root\Animals\Feline':
                return $this->simpleClass('Root\Animals\Feline', 'Feline');
            case 'Foo\Baz\Baz':
                return $this->simpleClass('Foo\Baz\Baz', 'Baz');
            case 'Foo\Baz\StaticClass':
                return $this->simpleClass('Foo\Baz\StaticClass', 'StaticClass');
            case 'Doctrine\MongoDB\Collection':
                return $this->simpleClass('Doctrine\MongoDB\Collection', 'Collection');
            default:
                throw new \Exception('Unexpected value');
        }
    }

    public function isContentAvailable(): bool
    {
        return true;
    }

    public function getError(): ?ParsingError
    {
        return null;
    }

    public function getFileName(): ?string
    {
        return $this->classFQCN;
    }

    public function getNotParsedClasses(): NotParsedClasses
    {
        return new NotParsedClasses();
    }

    private function simpleClass(string $namespace, string $className): string
    {
        return '<?php
declare(strict_types=1);

namespace '.$namespace.';

class '.$className.'
{
}
';
    }
}
