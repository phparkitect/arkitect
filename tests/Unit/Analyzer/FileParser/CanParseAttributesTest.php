<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer\FileParser;

use Arkitect\Analyzer\FileParserFactory;
use Arkitect\Analyzer\FullyQualifiedClassName;
use Arkitect\CLI\TargetPhpVersion;
use PHPUnit\Framework\TestCase;

class CanParseAttributesTest extends TestCase
{
    public function test_should_parse_class_attributes(): void
    {
        $code = <<< 'EOF'
        <?php

        use Bar\FooAttr;

        #[FooAttr('bar')]
        #[Baz]
        class Foo {}
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_0);
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        self::assertEquals(
            [
                FullyQualifiedClassName::fromString('Bar\\FooAttr'),
                FullyQualifiedClassName::fromString('Baz'),
            ],
            $cd[0]->getAttributes()
        );
    }

    public function test_it_should_parse_traits_attributes(): void
    {
        $code = <<< 'EOF'
        <?php

        namespace Root\Cars;

        use Bar\FooAttr;

        #[FooAttr('bar')]
        trait ATrait
        {
            #[Baz]
            public function foo(): string { return 'foo'; }
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_1);
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        self::assertEquals(
            [
                FullyQualifiedClassName::fromString('Bar\\FooAttr'),
                FullyQualifiedClassName::fromString('Root\\Cars\\Baz'),
            ],
            $cd[0]->getAttributes()
        );
    }

    public function test_should_parse_enum_attributes(): void
    {
        $code = <<< 'EOF'
        <?php

        namespace Root\Cars;

        use Bar\FooAttr;

        #[FooAttr('bar')]
        #[Baz]
        enum Enum
        {
            case Hearts;
            case Diamonds;
            case Clubs;
            case Spades;
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_1);
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        self::assertEquals(
            [
                FullyQualifiedClassName::fromString('Bar\\FooAttr'),
                FullyQualifiedClassName::fromString('Root\\Cars\\Baz'),
            ],
            $cd[0]->getAttributes()
        );
    }

    public function test_it_should_parse_interface_attributes(): void
    {
        $code = <<< 'EOF'
        <?php

        namespace Root\Cars;

        use Bar\FooAttr;

        #[FooAttr('bar')]
        interface AnInterface
        {
            #[Baz]
            public function foo(): string;
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_1);
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        self::assertEquals(
            [
                FullyQualifiedClassName::fromString('Bar\\FooAttr'),
                FullyQualifiedClassName::fromString('Root\\Cars\\Baz'),
            ],
            $cd[0]->getAttributes()
        );
    }
}
