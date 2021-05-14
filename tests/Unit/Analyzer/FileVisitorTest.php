<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\ClassDependency;
use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FileParser;
use Arkitect\Analyzer\FileParserFactory;
use PHPUnit\Framework\TestCase;

class FileVisitorTest extends TestCase
{
    public function test_should_create_a_class_description(): void
    {
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

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser();
        $fp->parse($code);
        $cd = $fp->getClassDescriptions();

        self::assertCount(2, $cd);
        self::assertInstanceOf(ClassDescription::class, $cd[0]);
        self::assertInstanceOf(ClassDescription::class, $cd[1]);
    }

    public function test_should_create_a_class_description_and_parse_anonymous_class(): void
    {
        $code = <<< 'EOF'
<?php

namespace Root\Namespace1;

use Root\Namespace2\D;

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

            $projector = new Proj();
    }
}

class Cat implements AnInterface
{

}
EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::createFileParser();
        $fp->parse($code);
        $cd = $fp->getClassDescriptions();

        self::assertCount(3, $cd);
        self::assertInstanceOf(ClassDescription::class, $cd[0]);
        self::assertInstanceOf(ClassDescription::class, $cd[1]);

        $expectedInterfaces = [
            new ClassDependency('Root\Namespace1\AnInterface', 7),
            new ClassDependency('\Root\Namespace1\AnInterface', 7),
            new ClassDependency('Root\Namespace1\InterfaceTwo', 7),
            new ClassDependency('\Root\Namespace1\InterfaceTwo', 7),
            new ClassDependency('Root\Namespace1\Another\ForbiddenInterface', 11),
            new ClassDependency('\Root\Namespace1\Another\ForbiddenInterface', 11),
        ];

        $this->assertEquals($expectedInterfaces, $cd[0]->getDependencies());
    }
}
