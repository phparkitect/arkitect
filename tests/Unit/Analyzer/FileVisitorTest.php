<?php
declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FileParser;
use PHPUnit\Framework\TestCase;

class FileVisitorTest extends TestCase
{
    public function test_should_create_a_class_description(): void
    {
        $cd = [];
        $fp = new FileParser();
        $fp->onClassAnalyzed(static function (ClassDescription $classDescription) use (&$cd): void {
            $cd[] = $classDescription;
        });

        $code = '<?php

            namespace Root\Namespace1;

            use Root\Namespace2\D;

            class Dog implements AnInterface, InterfaceTwo
            {
            }

            class Cat implements AnInterface
            {

            }
            ';

        $fp->parse($code);

        self::assertCount(2, $cd);
        self::assertInstanceOf(ClassDescription::class, $cd[0]);
        self::assertInstanceOf(ClassDescription::class, $cd[1]);
    }
}
