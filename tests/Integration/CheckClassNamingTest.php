<?php
declare(strict_types=1);

namespace Arkitect\Tests\Integration;

use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;
use Arkitect\Tests\Utils\TestRunner;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class CheckClassNamingTest extends TestCase
{
    public function test_code_in_happy_island_should_have_name_matching_prefix(): void
    {
        $dir = vfsStream::setup('root', null, $this->createDummyProject())->url();

        $runner = TestRunner::create('8.4');

        $rule = Rule::allClasses()
            ->that(new ResideInOneOfTheseNamespaces('App\HappyIsland'))
            ->should(new HaveNameMatching('Happy*'))
            ->because("that's what she said");

        $runner->run($dir, $rule);

        self::assertCount(0, $runner->getViolations());
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
