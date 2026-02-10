<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer\FileParser;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Analyzer\FileParserFactory;
use Arkitect\CLI\TargetPhpVersion;
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

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_4);
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        self::assertInstanceOf(ClassDescription::class, $cd[0]);
    }

    public function test_it_collects_dependencies_from_property_hooks(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace App\Foo;

        use App\Services\Formatter;
        use App\Services\Validator;
        use App\Services\Logger;

        class User {
            public string $name {
                get {
                    $formatter = new Formatter();
                    return $formatter->format($this->name);
                }
                set {
                    $validator = new Validator();
                    $validator->validate($value);
                    $this->name = $value;
                    Logger::log('Name set');
                }
            }
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_4);
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        self::assertInstanceOf(ClassDescription::class, $cd[0]);

        $dependencies = $cd[0]->getDependencies();
        $dependencyNames = array_map(static fn ($dep) => $dep->getFQCN()->toString(), $dependencies);

        self::assertContains('App\Services\Formatter', $dependencyNames);
        self::assertContains('App\Services\Validator', $dependencyNames);
        self::assertContains('App\Services\Logger', $dependencyNames);
    }

    public function test_it_collects_dependencies_from_property_hook_parameters(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace App\Foo;

        use App\ValueObjects\Name;

        class User {
            public string $name {
                set (Name $name) {
                    $this->name = $name->toString();
                }
            }
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_4);
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        self::assertInstanceOf(ClassDescription::class, $cd[0]);

        $dependencies = $cd[0]->getDependencies();
        $dependencyNames = array_map(static fn ($dep) => $dep->getFQCN()->toString(), $dependencies);

        self::assertContains('App\ValueObjects\Name', $dependencyNames);
    }
}
