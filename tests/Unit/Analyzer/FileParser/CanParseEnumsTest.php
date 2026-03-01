<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer\FileParser;

use Arkitect\Analyzer\FileParserFactory;
use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class CanParseEnumsTest extends TestCase
{
    /**
     * @requires PHP 8.1
     */
    public function test_it_can_parse_enum(): void
    {
        $code = file_get_contents(__DIR__.'/Fixtures/SampleEnum.php');
        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_1);
        $fp->parse($code, 'SampleEnum.php');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $notHaveDependencyOutsideNamespace = new Implement('MyInterface');
        $notHaveDependencyOutsideNamespace->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(1, $violations);
    }

    /**
     * @requires PHP 8.1
     */
    public function test_it_records_interface_dependency_for_enum_implementing_interface(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace App\Foo;

        interface Colorful {}

        enum Suit: string implements Colorful
        {
            case Hearts = 'H';
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_1);
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $enum = $cd[1]; // cd[0] = Colorful interface, cd[1] = Suit enum
        self::assertTrue($enum->isEnum());
        self::assertCount(1, $enum->getDependencies());
        self::assertEquals('App\Foo\Colorful', $enum->getDependencies()[0]->getFQCN()->toString());
    }

    /**
     * @dataProvider provide_enums
     */
    public function test_it_parse_enums(string $code): void
    {
        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_1);
        $fp->parse($code, 'relativePathName');

        foreach ($fp->getClassDescriptions() as $classDescription) {
            self::assertTrue($classDescription->isEnum());
        }
    }

    public static function provide_enums(): \Generator
    {
        yield 'default enum' => [
            <<< 'EOF'
            <?php
            namespace App\Foo;

            enum DefaultEnum
            {
                case FOO;
            }
            EOF,
        ];

        yield 'string enum' => [
            <<< 'EOF'
            <?php
            namespace App\Foo;

            enum StringEnum: string
            {
                case BAR: 'bar';
            }
            EOF,
        ];

        yield 'integer enum' => [
            <<< 'EOF'
            <?php
            namespace App\Foo;

            enum IntEnum: int
            {
                case BAZ: 42;
            }
            EOF,
        ];

        yield 'multiple enums' => [
            <<< 'EOF'
            <?php
            namespace App\Foo;

            enum DefaultEnum
            {
                case FOO;
            }

            enum IntEnum: int
            {
                case BAZ: 42;
            }

            enum IntEnum: int
            {
                case BAZ: 42;
            }
            EOF,
        ];
    }
}
