<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\ClassDescriptionBuilder;
use Arkitect\Analyzer\DocblockTypesResolver;
use Arkitect\Analyzer\FileParser;
use Arkitect\Analyzer\FileVisitor;
use Arkitect\CLI\TargetPhpVersion;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PHPUnit\Framework\TestCase;

class DocblockTypesResolverTest extends TestCase
{
    public function test_it_should_collect_dependencies_defined_in_docblock(): void
    {
        $parser = new FileParser(
            new NodeTraverser(),
            new FileVisitor(new ClassDescriptionBuilder()),
            new NameResolver(),
            new DocblockTypesResolver(true),
            TargetPhpVersion::latest()
        );

        $code = <<< 'EOF'
        <?php
        namespace Domain\Foo;

        use Application\MyDto;
        use Domain\ValueObject;
        use Symfony\Component\Validator\Constraints AS Assert;

        use Application\Model\{User, Product};

        class MyClass
        {
            /** @var array<int, int|string> */
            public array $myArray;

            /**
             * @var array<int, User>
             */
            public array $users;

            /**
             * @Assert\NotBlank
             */
            public array $myAssertedArray;

            /**
             * @phpstan-ignore property.readOnlyByPhpDocDefaultValue
             */
            private static ?\UuidFactoryInterface $factory = null;

            /**
             * @param MyDto[] $dtoList
             * @param int $var2
             * @param ValueObject[] $voList
             */
            public function __construct(string $var1, array $dtoList, $var2, array $voList)
            {
            }

            /**
             * @param User[] $users
             * @param Product[] $products
             */
            public function myMethod(array $users, array $products, MyOtherClass $other): void
            {
            }

            /**
             *
             * @param array<int, int|string> $aParam
             * @param array<int, User> $users
             *
             * @return array<int, int|string>
             */
            public function myMethod2(array $aParam, array $users): array
        }
        EOF;

        $parser->parse($code, 'src/path/file.php');

        $cd = $parser->getClassDescriptions()[0];
        $dep = $cd->getDependencies();

        self::assertCount(9, $cd->getDependencies());
        self::assertEquals('Application\Model\User', $dep[0]->getFQCN()->toString());
        self::assertEquals('Symfony\Component\Validator\Constraints\NotBlank', $dep[1]->getFQCN()->toString());
        self::assertEquals('UuidFactoryInterface', $dep[2]->getFQCN()->toString());
        self::assertEquals('Application\MyDto', $dep[3]->getFQCN()->toString());
        self::assertEquals('Domain\ValueObject', $dep[4]->getFQCN()->toString());
        self::assertEquals('Application\Model\User', $dep[5]->getFQCN()->toString());
        self::assertEquals('Application\Model\Product', $dep[6]->getFQCN()->toString());
        self::assertEquals('Domain\Foo\MyOtherClass', $dep[7]->getFQCN()->toString());
        self::assertEquals('Application\Model\User', $dep[8]->getFQCN()->toString());
    }
}
