<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\DocblockParserFactory;
use PHPUnit\Framework\TestCase;

class DocblockParserTest extends TestCase
{
    public function test_it_should_exctract_types_from_param_tag(): void
    {
        $parser = DocblockParserFactory::create();

        $code = <<< 'PHP'
            /**
             * @param MyDto[] $dtoList
             * @param list<MyOtherDto> $dtoList2
             * @param array<int, ValueObject> $voList
             * @param array<User> $user
             * @param array<User> $user
             * @param int $aValue
             * @param MyPlainDto $plainDto
             * @param array<int, int|string> $unionType
             */
        PHP;

        $db = $parser->parse($code);

        self::assertEquals('MyDto', $db->getParamTagTypesByName('$dtoList'));
        self::assertEquals('MyOtherDto', $db->getParamTagTypesByName('$dtoList2'));
        self::assertEquals('ValueObject', $db->getParamTagTypesByName('$voList'));
        self::assertEquals('User', $db->getParamTagTypesByName('$user'));

        self::assertEquals('int', $db->getParamTagTypesByName('$aValue'));
        self::assertEquals('MyPlainDto', $db->getParamTagTypesByName('$plainDto'));
        self::assertEquals('(int | string)', $db->getParamTagTypesByName('$unionType'));
    }

    public function test_it_should_extract_return_type_from_return_tag(): void
    {
        $parser = DocblockParserFactory::create();

        $code = <<< 'PHP'
            /**
             * @return MyDto[]
             * @return list<MyOtherDto>
             * @return array<int, ValueObject>
             * @return array<User>
             * @return int
             * @return MyPlainDto
             * @return array<int, int|string>
             */
        PHP;

        $db = $parser->parse($code);

        $returnTypes = $db->getReturnTagTypes();
        self::assertCount(7, $returnTypes);
        self::assertEquals('MyDto', $returnTypes[0]);
        self::assertEquals('MyOtherDto', $returnTypes[1]);
        self::assertEquals('ValueObject', $returnTypes[2]);
        self::assertEquals('User', $returnTypes[3]);
        self::assertEquals('int', $returnTypes[4]);
        self::assertEquals('MyPlainDto', $returnTypes[5]);
        self::assertEquals('(int | string)', $returnTypes[6]);
    }

    public function test_it_should_extract_types_from_var_tag(): void
    {
        $parser = DocblockParserFactory::create();

        $code = <<< 'PHP'
            /**
             * @var MyDto[] $dtoList
             * @var list<MyOtherDto> $dtoList2
             * @var array<int, ValueObject> $voList
             * @var array<User> $user
             * @var int $aValue
             * @var MyPlainDto $plainDto
             * @var array<int, int|string> $unionType
             */
        PHP;

        $db = $parser->parse($code);

        $varTags = $db->getVarTagTypes();
        self::assertCount(7, $varTags);
        self::assertEquals('MyDto', $varTags[0]);
        self::assertEquals('MyOtherDto', $varTags[1]);
        self::assertEquals('ValueObject', $varTags[2]);
        self::assertEquals('User', $varTags[3]);
        self::assertEquals('int', $varTags[4]);
        self::assertEquals('MyPlainDto', $varTags[5]);
        self::assertEquals('(int | string)', $varTags[6]);
    }

    public function test_it_should_extract_doctrine_like_annotations(): void
    {
        $parser = DocblockParserFactory::create();

        $code = <<< 'PHP'
            /**
             * @ORM\Id
             * @ORM\Column(type="integer")
             * @ORM\GeneratedValue
             * @Assert\NotBlank
             * @Assert\Length(min=5)
             */
        PHP;

        $db = $parser->parse($code);

        $doctrineAnnotations = $db->getDoctrineLikeAnnotationTypes();

        self::assertCount(5, $doctrineAnnotations);
        self::assertEquals('ORM\Id', $doctrineAnnotations[0]);
        self::assertEquals('ORM\Column', $doctrineAnnotations[1]);
        self::assertEquals('ORM\GeneratedValue', $doctrineAnnotations[2]);
        self::assertEquals('Assert\NotBlank', $doctrineAnnotations[3]);
        self::assertEquals('Assert\Length', $doctrineAnnotations[4]);
    }
}
