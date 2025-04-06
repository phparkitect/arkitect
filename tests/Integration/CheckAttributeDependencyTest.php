<?php

declare(strict_types=1);

namespace Arkitect\Tests\Integration;

use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;
use Arkitect\Tests\Utils\TestRunner;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class CheckAttributeDependencyTest extends TestCase
{
    public function test_assertion_should_fail_on_invalid_dependency(): void
    {
        $dir = vfsStream::setup('root', null, $this->createDirStructureWithAttributes())->url();

        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App'))
            ->should(new NotDependsOnTheseNamespaces('App\Invalid'))
            ->because('i said so');

        $runner->run($dir, $rule);

        self::assertCount(1, $runner->getViolations());
        self::assertCount(0, $runner->getParsingErrors());

        self::assertStringContainsString('depends on App\Invalid\Attr, but should not depend on these namespaces: App\Invalid', $runner->getViolations()->get(0)->getError());
    }

    public function createDirStructureWithAttributes(): array
    {
        return [
            'App' => [
                'Foo.php' => '<?php
                    declare(strict_types=1);

                    namespace App;

                    use App\Invalid\Attr as InvalidAttr;
                    use App\Valid\Attr as ValidAttr;

                    #[ValidAttr, InvalidAttr]
                    class Foo
                    {
                    }
                ',
                'Valid' => [
                    'Attr.php' => '<?php
                        declare(strict_types=1);

                        namespace App\Valid;

                        use Attribute;

                        #[Attribute]
                        class Attr
                        {
                        }
                        ',
                ],
                'Invalid' => [
                    'Attr.php' => '<?php
                        declare(strict_types=1);

                        namespace App\Invalid;

                        use Attribute;

                        #[Attribute]
                        class Attr
                        {
                        }
                    ',
                ],
            ],
        ];
    }
}
