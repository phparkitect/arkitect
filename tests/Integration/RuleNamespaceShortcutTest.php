<?php
declare(strict_types=1);

namespace Arkitect\Tests\Integration;

use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;
use Arkitect\Tests\Utils\TestRunner;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class RuleNamespaceShortcutTest extends TestCase
{
    public function test_namespace_shortcut_works_same_as_full_syntax(): void
    {
        $dir = vfsStream::setup('root', null, $this->createDummyProject())->url();

        $runner = TestRunner::create('8.4');

        $rule = Rule::namespace('App\HappyIsland')
            ->should(new HaveNameMatching('Happy*'))
            ->because("that's what she said");

        $runner->run($dir, $rule);

        self::assertCount(0, $runner->getViolations());
    }

    public function test_namespace_shortcut_detects_violations(): void
    {
        $dir = vfsStream::setup('root', null, $this->createDummyProject())->url();

        $runner = TestRunner::create('8.4');

        $rule = Rule::namespace('App\HappyIsland')
            ->should(new HaveNameMatching('Sad*'))
            ->because('we want sad names');

        $runner->run($dir, $rule);

        self::assertCount(1, $runner->getViolations());
    }

    public function test_namespace_shortcut_supports_multiple_namespaces(): void
    {
        $dir = vfsStream::setup('root', null, $this->createDummyProject())->url();

        $runner = TestRunner::create('8.4');

        $rule = Rule::namespace('App\HappyIsland', 'App\BadCode')
            ->should(new HaveNameMatching('*Code'))
            ->because('we want Code suffix');

        $runner->run($dir, $rule);

        // HappyClass doesn't match *Code pattern, so we should have 1 violation
        self::assertCount(1, $runner->getViolations());
    }

    public function test_namespace_shortcut_is_equivalent_to_full_syntax(): void
    {
        $dir = vfsStream::setup('root', null, $this->createDummyProject())->url();

        $runner = TestRunner::create('8.4');

        // Using shortcut
        $shortcutRule = Rule::namespace('App\HappyIsland')
            ->should(new HaveNameMatching('Happy*'))
            ->because('test');

        $runner->run($dir, $shortcutRule);
        $shortcutViolations = $runner->getViolations();

        // Using full syntax
        $fullRule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\HappyIsland'))
            ->should(new HaveNameMatching('Happy*'))
            ->because('test');

        $runner->run($dir, $fullRule);
        $fullViolations = $runner->getViolations();

        self::assertCount(\count($shortcutViolations), $fullViolations);
    }

    public function createDummyProject(): array
    {
        return [
            'BadCode' => [
                'BadCode.php' => <<<'EOF'
                    <?php

                    namespace App\BadCode;

                    class BadCode
                    {
                        private $happy;

                        public function __construct(HappyClass $happy)
                        {
                            $this->happy = $happy;
                        }
                    }
                    EOF,
            ],
            'OtherBadCode' => [
                'OtherBadCode.php' => <<<'EOF'
                    <?php

                    namespace App\BadCode;

                    class OtherBadCode
                    {
                        private $happy;

                        public function __construct(HappyClass $happy)
                        {
                            $this->happy = $happy;
                        }
                    }
                    EOF,
            ],

            'HappyIsland' => [
                'HappyClass.php' => <<<'EOF'
                    <?php

                    namespace App\HappyIsland;

                    class HappyClass
                    {
                        /**
                         * @var BadCode
                         */
                        private $bad;

                        public function __construct(BadCode $bad)
                        {
                            $this->bad = $bad;
                        }
                    }
                    EOF,
            ],
        ];
    }
}
