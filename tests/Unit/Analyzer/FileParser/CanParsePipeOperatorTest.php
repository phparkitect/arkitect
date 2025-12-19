<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer\FileParser;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FileParserFactory;
use Arkitect\CLI\TargetPhpVersion;
use PHPUnit\Framework\TestCase;

class CanParsePipeOperatorTest extends TestCase
{
    public function test_it_parse_pipe_operator(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace App\Foo;

        class StringProcessor {
            public function process(string $input): array
            {
                return $input
                    |> strtolower(...)
                    |> str_split(...)
                    |> (fn($x) => array_map(strtoupper(...), $x));
            }

            public function complexPipe(string $text): string
            {
                return $text
                    |> trim(...)
                    |> (fn($x) => str_replace(' ', '_', $x))
                    |> strtoupper(...);
            }

            public function simplePipe(int $number): int
            {
                return $number
                    |> (fn($x) => $x * 2)
                    |> (fn($x) => $x + 10);
            }
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_5);
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        self::assertInstanceOf(ClassDescription::class, $cd[0]);
        self::assertCount(1, $cd);
        self::assertEquals('App\Foo\StringProcessor', $cd[0]->getFQCN());
    }

    public function test_it_handles_pipe_operator_without_errors(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace App\Services;

        class Calculator {
            public function calculate(float $value): float
            {
                return $value
                    |> (fn($x) => $x * 1.5)
                    |> (fn($x) => round($x, 2));
            }
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_5);
        $fp->parse($code, 'Calculator.php');

        $errors = $fp->getParsingErrors();
        if (!empty($errors)) {
            $errorMessages = array_map(fn($e) => $e->getError(), $errors);
            self::fail('Expected no parsing errors for pipe operator syntax. Errors: ' . implode(', ', $errorMessages));
        }

        self::assertCount(1, $fp->getClassDescriptions());
    }
}
