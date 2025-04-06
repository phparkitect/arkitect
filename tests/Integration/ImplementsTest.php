<?php

declare(strict_types=1);

namespace Arkitect\Tests\Integration;

use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Rules\Rule;
use Arkitect\Tests\Utils\TestRunner;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class ImplementsTest extends TestCase
{
    public function test_naming_is_enforced(): void
    {
        $dir = vfsStream::setup('root', null, $this->createDirStructure())->url();

        $runner = TestRunner::create('8.2');

        $rule = Rule::allClasses()
            ->that(new Implement('App\AnInterface'))
            ->should(new HaveNameMatching('An*'))
            ->because('reasons');

        $runner->run($dir, $rule);

        self::assertCount(0, $runner->getParsingErrors());
        self::assertCount(2, $runner->getViolations());

        self::assertEquals('App\AClass', $runner->getViolations()->get(0)->getFqcn());
        self::assertStringContainsString('should have a name that matches An* because reasons', $runner->getViolations()->get(0)->getError());

        self::assertEquals('App\AEnum', $runner->getViolations()->get(1)->getFqcn());
        self::assertStringContainsString('should have a name that matches An* because reasons', $runner->getViolations()->get(1)->getError());
    }

    public function createDirStructure(): array
    {
        return [
            'App' => [
                'AEnum.php' => '<?php

                    namespace App;

                    enum AEnum implements AnInterface {
                        case PENDING;
                        case PAYED;

                        public function amethod() {}
                    }
                    ',
                'OneTrait.php' => '<?php

                    namespace App;

                    trait OneTrait {
                        public function one() {}
                    }
                    ',
                'AClass.php' => '<?php

                    namespace App;

                    class AClass implements AnInterface {
                        public function amethod() {}
                    }
                    ',
                'AnInterface.php' => '<?php

                    namespace App;

                    interface AnInterface {
                        public function amethod();
                    }
                    ',
            ],
        ];
    }
}
