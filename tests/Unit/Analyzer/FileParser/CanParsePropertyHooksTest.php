<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer\FileParser;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FileParserFactory;
use PHPUnit\Framework\TestCase;

class CanParsePropertyHooksTest extends TestCase
{
    public function test_it_parse_property_hooks(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace App\Foo;

        class User {
            private string $firstName;
            private string $lastName;

            public function __construct(string $firstName, string $lastName) {
                $this->firstName = $firstName;
                $this->lastName = $lastName;
            }

            public string $fullName {
                get => $this->firstName . ' ' . $this->lastName;
                set {[$this->firstName, $this->lastName] = explode(' ', $value, 2);}
            }
        }
        EOF;

        /** @var FileParser $fp */
        $fp = FileParserFactory::forLatestPhpVersion();
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        self::assertInstanceOf(ClassDescription::class, $cd[0]);
    }
}
