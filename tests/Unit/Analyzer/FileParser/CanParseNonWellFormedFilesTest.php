<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer\FileParser;

use Arkitect\Analyzer\ClassDescriptions;
use Arkitect\Analyzer\FileParserFactory;
use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Rules\ParsingError;
use Arkitect\Rules\ParsingErrors;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class CanParseNonWellFormedFilesTest extends TestCase
{
    public function test_should_parse_non_php_file(): void
    {
        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_0);
        $result = $fp->parse('', 'path/to/class.php');

        self::assertInstanceOf(ClassDescriptions::class, $result);
        self::assertCount(0, $result);
    }

    public function test_should_parse_empty_file(): void
    {
        $code = <<< 'EOF'
        <?php
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_0);
        $result = $fp->parse($code, 'path/to/class.php');

        self::assertInstanceOf(ClassDescriptions::class, $result);
        self::assertCount(0, $result);
    }

    public function test_it_should_catch_parsing_errors(): void
    {
        $code = <<< 'EOF'
        <?php

        namespace Root\Animals;

        class Animal
        {
            public function __construct()
            {
            FOO
            }
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_0);
        $result = $fp->parse($code, 'relativePathName');

        self::assertInstanceOf(ParsingErrors::class, $result);
        self::assertEquals([
            ParsingError::create('relativePathName', 'Syntax error, unexpected \'}\' on line 10'),
        ], $result->toArray());
    }

    public function test_null_class_description_builder(): void
    {
        $code = <<< 'EOF'
        <?php

        declare(strict_types=1);

        namespace App\Application\Command;

        use App\Domain\Quote\Quote;

        interface QuoteCommandInterface
        {
            /**
             * Save a stock quote.
             */
            public function save(Quote $quote): void;
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_0);
        $fp->parse($code, 'relativePathName');

        $violations = new Violations();

        self::assertCount(0, $violations);
    }
}
