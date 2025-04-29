<?php

declare(strict_types=1);

namespace Arkitect\Tests\Integration;

use Arkitect\Expression\ForClasses\Extend;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Rules\Rule;
use Arkitect\Tests\Utils\TestRunner;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class ExtendsThrowableTest extends TestCase
{
    public function test_naming_is_enforced(): void
    {
        $dir = vfsStream::setup('root', null, $this->createDirStructure())->url();

        $runner = TestRunner::create('8.2');

        $rule = Rule::allClasses()
            ->that(new Extend(\Throwable::class))
            ->should(new HaveNameMatching('*Exception'))
            ->because('reasons');

        $runner->run($dir, $rule);

        self::assertCount(1, $runner->getViolations());
        self::assertCount(0, $runner->getParsingErrors());

        self::assertStringContainsString('should have a name that matches *Exception because', $runner->getViolations()->get(0)->getError());
    }

    public function createDirStructure(): array
    {
        return [
            'App' => [
                'BillingEnum.php' => '<?php

                    namespace App;

                    enum BillingEnum {
                        case PENDING;
                        case PAID;
                    }
                    ',
                'AnException.php' => '<?php

                    namespace App;

                    class AnException extends \Throwable { }
                    ',
                'AThrowable.php' => '<?php

                    namespace App;

                    class AThrowable extends \Throwable { }
                    ',
                'OneTrait.php' => '<?php

                    namespace App;

                    trait OneTrait {
                        public function one() {}
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
