<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer\FileParser;

use Arkitect\Analyzer\FileParserFactory;
use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Expression\ForClasses\DependsOnlyOnTheseNamespaces;
use Arkitect\Expression\ForClasses\NotHaveDependencyOutsideNamespace;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class CanParseClassPropertiesTest extends TestCase
{
    public function test_it_parse_typed_property(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace MyProject\AppBundle\Application;

        use Symfony\Component\Validator\Constraints\NotBlank;

        class ApplicationLevelDto
        {
            public NotBlank $foo;
        }
        EOF;

        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.1'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $notHaveDependencyOutsideNamespace = new DependsOnlyOnTheseNamespaces(['MyProject\AppBundle\Application']);
        $notHaveDependencyOutsideNamespace->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(1, $violations);
    }

    public function test_it_parse_typed_nullable_property(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace MyProject\AppBundle\Application;

        use Symfony\Component\Validator\Constraints\NotBlank;

        class ApplicationLevelDto
        {
            public ?NotBlank $foo;
        }
        EOF;

        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.1'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $notHaveDependencyOutsideNamespace = new DependsOnlyOnTheseNamespaces(['MyProject\AppBundle\Application']);
        $notHaveDependencyOutsideNamespace->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(1, $violations);
    }

    public function test_it_parse_scalar_typed_property(): void
    {
        $code = <<< 'EOF'
        <?php

        namespace MyProject\AppBundle\Application;

        class ApplicationLevelDto
        {
            public bool $fooBool;
            public int $fooInt;
            public float $fooFloat;
            public string $fooString;
        }
        EOF;

        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.1'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $notHaveDependencyOutsideNamespace = new NotHaveDependencyOutsideNamespace('MyProject\AppBundle\Application');
        $notHaveDependencyOutsideNamespace->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(0, $violations);
    }

    public function test_it_parse_nullable_scalar_typed_property(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace MyProject\AppBundle\Application;
        class ApplicationLevelDto
        {
            public function __construct(
                ?bool $fooBool,
                ?int $fooInt,
                ?float $fooFloat,
                ?string $fooString
            ) {

            }
        }
        EOF;

        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.1'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $notHaveDependencyOutsideNamespace = new NotHaveDependencyOutsideNamespace('MyProject\AppBundle\Application');
        $notHaveDependencyOutsideNamespace->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(0, $violations);
    }

    public function test_it_parse_arrays_as_scalar_types(): void
    {
        $code = <<< 'EOF'
        <?php

        namespace App\Domain;

        class MyClass
        {
            private array $field1;
            public function __construct(array $field1)
            {
                $this->field1 = $field1;
            }
        }
        EOF;

        $fp = FileParserFactory::createFileParser(TargetPhpVersion::create('8.1'));
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $notHaveDependenciesOutside = new NotHaveDependencyOutsideNamespace('App\Domain');
        $notHaveDependenciesOutside->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(0, $violations);
    }
}
