<?php

declare(strict_types=1);

namespace Arkitect\Tests\Integration;

use Arkitect\Expression\ForClasses\Extend;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Rules\Rule;
use Arkitect\Tests\Utils\TestRunner;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class InheritedInterfacesTest extends TestCase
{
    public function test_implement_rule_matches_inherited_interfaces(): void
    {
        $dir = vfsStream::setup('root', null, $this->createDirStructure())->url();

        $runner = TestRunner::create('8.2');

        // Countable is inherited from ArrayObject, not directly implemented by MyCollection
        $rule = Rule::allClasses()
            ->that(new Implement('Countable'))
            ->should(new HaveNameMatching('*Collection'))
            ->because('classes implementing Countable should be named *Collection');

        $runner->run($dir, $rule);

        self::assertCount(0, $runner->getParsingErrors());

        // MyCollection extends ArrayObject which implements Countable
        // So MyCollection should match the rule and have no violations
        // (it implements Countable indirectly and is named *Collection)
        self::assertCount(0, $runner->getViolations());
    }

    public function test_extend_rule_matches_ancestor_classes(): void
    {
        $dir = vfsStream::setup('root', null, $this->createExtendsStructure())->url();

        $runner = TestRunner::create('8.2');

        // LogicException is a grandparent of MyException (via InvalidArgumentException)
        $rule = Rule::allClasses()
            ->that(new Extend('LogicException'))
            ->should(new HaveNameMatching('*Exception'))
            ->because('classes extending LogicException should be named *Exception');

        $runner->run($dir, $rule);

        self::assertCount(0, $runner->getParsingErrors());
        self::assertCount(0, $runner->getViolations());
    }

    public function createDirStructure(): array
    {
        return [
            'App' => [
                'MyCollection.php' => '<?php

                    namespace App;

                    class MyCollection extends \ArrayObject {
                        public function customMethod(): void {}
                    }
                    ',
            ],
        ];
    }

    public function createExtendsStructure(): array
    {
        return [
            'App' => [
                'MyException.php' => '<?php

                    namespace App;

                    class MyException extends \InvalidArgumentException {
                    }
                    ',
            ],
        ];
    }
}
