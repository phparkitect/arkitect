<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Analyzer;

use Arkitect\Analyzer\Docblock;
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
             */
        }
        PHP;

        $phpdoc = $parser->parse($code);

        $db = new Docblock($phpdoc);

        self::assertEquals('MyDto', $db->getParamTagTypesByName('$dtoList'));
        self::assertEquals('MyOtherDto', $db->getParamTagTypesByName('$dtoList2'));
        self::assertEquals('ValueObject', $db->getParamTagTypesByName('$voList'));
        self::assertEquals('User', $db->getParamTagTypesByName('$user'));

        self::assertNull($db->getParamTagTypesByName('$aValue'));
        self::assertNull($db->getParamTagTypesByName('$plainDto'));
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
             */
        }
        PHP;

        $phpdoc = $parser->parse($code);

        $db = new Docblock($phpdoc);

        $returnTypes = $db->getReturnTagTypes();
        self::assertCount(4, $returnTypes);
        self::assertEquals('MyDto', $returnTypes[0]);
        self::assertEquals('MyOtherDto', $returnTypes[1]);
        self::assertEquals('ValueObject', $returnTypes[2]);
        self::assertEquals('User', $returnTypes[3]);
    }
}
