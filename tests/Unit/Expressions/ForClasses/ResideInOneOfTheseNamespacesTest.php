<?php

declare(strict_types=1);

namespace Arkitect\Tests\Unit\Expressions\ForClasses;

use Arkitect\Analyzer\ClassDescription;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use PHPUnit\Framework\TestCase;

class ResideInOneOfTheseNamespacesTest extends TestCase
{
    public function shouldMatchNamespacesProvider(): array
    {
        return [
            ['Food\Vegetables', 'Food\Vegetables\Carrot', 'matches a class in the root namespace'],
            ['Food\Vegetables', 'Food\Vegetables\Roots\Carrot', 'matches a class in a child namespace'],
            ['Food\Vegetables', 'Food\Vegetables\Roots\Orange\Carrot', 'matches a class in a child of a child namespace'],
        ];
    }

    /**
     * @dataProvider shouldMatchNamespacesProvider
     *
     * @param mixed $expectedNamespace
     * @param mixed $actualFQCN
     */
    public function testItShouldMatchNamespaceAndDescendants($expectedNamespace, $actualFQCN): void
    {
        $haveNameMatching = new ResideInOneOfTheseNamespaces($expectedNamespace);

        $classDesc = ClassDescription::build($actualFQCN)->get();

        self::assertTrue($haveNameMatching->evaluate($classDesc));
    }

    public function testItShouldReturnFalseIfNotResideInNamespace(): void
    {
        $haveNameMatching = new ResideInOneOfTheseNamespaces('MyNamespace');

        $classDesc = ClassDescription::build('AnotherNamespace\HappyIsland')->get();

        self::assertFalse($haveNameMatching->evaluate($classDesc));
    }

    public function testItShouldCheckMultipleNamespacesInOr(): void
    {
        $haveNameMatching = new ResideInOneOfTheseNamespaces('MyNamespace', 'AnotherNamespace', 'AThirdNamespace');

        $classDesc = ClassDescription::build('AnotherNamespace\HappyIsland')->get();
        self::assertTrue($haveNameMatching->evaluate($classDesc));

        $classDesc = ClassDescription::build('MyNamespace\HappyIsland')->get();
        self::assertTrue($haveNameMatching->evaluate($classDesc));

        $classDesc = ClassDescription::build('AThirdNamespace\HappyIsland')->get();
        self::assertTrue($haveNameMatching->evaluate($classDesc));

        $classDesc = ClassDescription::build('NopeNamespace\HappyIsland')->get();
        self::assertFalse($haveNameMatching->evaluate($classDesc));
    }
}
