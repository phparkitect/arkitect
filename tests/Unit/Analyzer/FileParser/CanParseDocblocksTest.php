<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer\FileParser;

use Arkitect\Analyzer\FileParserFactory;
use Arkitect\CLI\TargetPhpVersion;
use Arkitect\Expression\ForClasses\DependsOnlyOnTheseNamespaces;
use Arkitect\Expression\ForClasses\NotContainDocBlockLike;
use Arkitect\Rules\Violations;
use PHPUnit\Framework\TestCase;

class CanParseDocblocksTest extends TestCase
{
    public function test_it_parse_docblocks(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace Root\Cars;

        /**
        * @throws Exception
        */
        class Bar
        {
            /**
             * @throws ItemNotFound
             */
            public function getFoo(): int
            {
                return 1;
            }
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_1);
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $notHaveDependencyOutsideNamespace = new NotContainDocBlockLike('ItemNotFound');
        $notHaveDependencyOutsideNamespace->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(1, $violations);

        $notHaveDependencyOutsideNamespace = new NotContainDocBlockLike('Exception');
        $notHaveDependencyOutsideNamespace->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(2, $violations);
    }

    public function test_it_parse_dependencies_in_docblocks_customs(): void
    {
        $code = <<< 'EOF'
        <?php

        namespace MyProject\AppBundle\Application;

        use Symfony\Component\Validator\Constraints\NotBlank;

        class ApplicationLevelDto
        {
            /**
            * @var NotBlank
            */
            public $foo;

            /**
            * @var array<int, \stdClass>
            */
            public $baz;
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_1);
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $notHaveDependencyOutsideNamespace = new DependsOnlyOnTheseNamespaces(['MyProject\AppBundle\Application']);
        $notHaveDependencyOutsideNamespace->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(1, $violations);
    }

    public function test_it_handles_typed_arrays_in_properties_with_generics_syntax(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace Domain\Foo;

        use Application\MyDto;

        class MyClass
        {
            /**
             * @var array<int, MyDto>
             */
            private array $dtoList;
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_0);
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['Domain']);
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(1, $violations);
    }

    public function test_it_handles_typed_arrays_in_properties_with_list_syntax(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace Domain\Foo;

        use Application\MyDto;

        class MyClass
        {
            /**
             * @var list<MyDto>
             */
            private array $dtoList;
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_0);
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['Domain']);
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(1, $violations);
    }

    public function test_it_handles_typed_arrays_in_properties_with_legacy_syntax(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace Domain\Foo;

        use Application\MyDto;

        class MyClass
        {
            /**
             * @var MyDto[]
             */
            private array $dtoList;
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_0);
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['Domain']);
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(1, $violations);
    }

    public function test_it_handles_typed_arrays_in_method_params_with_generics_syntax(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace Domain\Foo;

        use Application\MyDto;

        class MyClass
        {
            /**
             * @param array<int, MyDto> $dtoList
             */
            public function __construct(array $dtoList)
            {
            }
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_0);
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['Domain']);
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(1, $violations);
    }

    public function test_it_handles_typed_arrays_in_method_params_with_list_syntax(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace Domain\Foo;

        use Application\MyDto;

        class MyClass
        {
            /**
             * @param list<MyDto> $dtoList
             */
            public function __construct(array $dtoList)
            {
            }
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_0);
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['Domain']);
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(1, $violations);
    }

    public function test_it_handles_typed_arrays_in_method_params_with_legacy_syntax(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace Domain\Foo;

        use Application\MyDto;

        class MyClass
        {
            /**
             * @param MyDto[] $dtoList
             */
            public function __construct(array $dtoList)
            {
            }
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_0);
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['Domain']);
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(1, $violations);
    }

    public function test_it_handles_typed_arrays_in_method_params_with_multiple_params(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace Domain\Foo;

        use Application\MyDto;
        use Domain\ValueObject;

        class MyClass
        {
            /**
             * @param MyDto[] $dtoList
             * @param int $var2
             * @param ValueObject[] $voList
             */
            public function __construct(string $var1, array $dtoList, $var2, array $voList)
            {
            }
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_0);
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['Domain']);
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(1, $violations);
    }

    public function test_it_handles_typed_arrays_in_return_type_with_generics_syntax(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace Domain\Foo;

        use Application\MyDto;

        class MyClass
        {
            /**
             * @return array<int, MyDto>
             */
            public function getList(): array
            {
                return [];
            }
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_0);
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['Domain']);
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(1, $violations);
    }

    public function test_it_handles_typed_arrays_in_return_type_with_list_syntax(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace Domain\Foo;

        use Application\MyDto;

        class MyClass
        {
            /**
             * @return list<MyDto>
             */
            public function getList(): array
            {
                return [];
            }
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_0);
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['Domain']);
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(1, $violations);
    }

    public function test_it_handles_typed_arrays_in_return_type_with_legacy_syntax(): void
    {
        $code = <<< 'EOF'
        <?php
        namespace Domain\Foo;

        use Application\MyDto;

        class MyClass
        {
            /**
             * @return MyDto[]
             */
            public function getList(): array
            {
                return [];
            }
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_0);
        $fp->parse($code, 'relativePathName');
        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['Domain']);
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(1, $violations);
    }

    public function test_it_skip_custom_annotations_in_docblocks_if_the_option_parse_custom_annotation_is_false(): void
    {
        $code = <<< 'EOF'
        <?php

        namespace MyProject\AppBundle\Application;

        use Symfony\Component\Validator\Constraints as Assert;

        class ApplicationLevelDto
        {
            /**
            * @Assert\NotBlank
            */
            public $foo;
        }
        EOF;

        $fp = FileParserFactory::createFileParser(
            TargetPhpVersion::create(TargetPhpVersion::PHP_8_1),
            false
        );
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnlyOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['MyProject\AppBundle\Application']);
        $dependsOnlyOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(0, $violations);
    }

    public function test_it_parse_dependencies_in_docblocks_with_alias(): void
    {
        $code = <<< 'EOF'
        <?php

        namespace MyProject\AppBundle\Application;

        use Symfony\Component\Validator\Constraints as Assert;
        use Symfony\Test;

        class ApplicationLevelDto
        {
            /**
             * @Assert\NotBlank
             */
            public string|null $foo;
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_1);
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $dependsOnTheseNamespaces = new DependsOnlyOnTheseNamespaces(['MyProject\AppBundle\Application']);
        $dependsOnTheseNamespaces->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(1, $violations);
    }

    public function test_it_parse_custom_tags_in_docblocks(): void
    {
        $code = <<< 'EOF'
        <?php

        namespace MyProject\AppBundle\Application;

        use Symfony\Component\Validator\Constraints as Assert;

        class ApplicationLevelDto
        {
        /**
        * @Assert\NotBlank
        */
            public $foo;

        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_1);
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        $violations = new Violations();

        $notHaveDependencyOutsideNamespace = new DependsOnlyOnTheseNamespaces(['MyProject\AppBundle\Application']);
        $notHaveDependencyOutsideNamespace->evaluate($cd[0], $violations, 'we want to add this rule for our software');

        self::assertCount(1, $violations);
    }

    public function test_it_collects_throws_tag_as_dependencies(): void
    {
        $code = <<< 'EOF'
        <?php

        namespace Domain\Foo;

        use Domain\FooException;
        use Domain\BarException;

        class MyClass
        {
            /**
             * @throws FooException
             * @throws BarException
             */
            public function method1()
            {
            }

            /**
             * @throws \Exception
             */
            public function method2()
            {
            }
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_7_4);
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        self::assertCount(1, $cd);
        $dependencies = $cd[0]->getDependencies();

        // Should have 3 dependencies from @throws: FooException, BarException, Exception
        self::assertCount(3, $dependencies);

        $fqcns = array_map(static fn ($dep) => $dep->getFQCN()->toString(), $dependencies);
        self::assertContains('Domain\FooException', $fqcns);
        self::assertContains('Domain\BarException', $fqcns);
        self::assertContains('Exception', $fqcns);
    }

    public function test_it_collects_throws_tag_with_fully_qualified_names(): void
    {
        $code = <<< 'EOF'
        <?php

        namespace App\Services;

        class MyService
        {
            /**
             * @throws \Exception
             * @throws \Domain\FooException
             * @throws BarException
             */
            public function doSomething()
            {
            }
        }
        EOF;

        $fp = FileParserFactory::forPhpVersion(TargetPhpVersion::PHP_8_1);
        $fp->parse($code, 'relativePathName');

        $cd = $fp->getClassDescriptions();

        self::assertCount(1, $cd);
        $dependencies = $cd[0]->getDependencies();

        // Should have 3 dependencies from @throws
        self::assertCount(3, $dependencies);

        $fqcns = array_map(static fn ($dep) => $dep->getFQCN()->toString(), $dependencies);
        self::assertContains('Exception', $fqcns);
        self::assertContains('Domain\FooException', $fqcns);
        self::assertContains('App\Services\BarException', $fqcns);
    }
}
